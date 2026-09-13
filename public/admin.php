<?php
require __DIR__ . '/../app/bootstrap.php';

// 后台响应一律禁缓存：防止浏览器 / 返回键(bfcache) 回放过期的登录表单与其中的 CSRF 令牌
// （过期令牌与当前会话不匹配即触发「CSRF 校验失败」）；管理页含敏感信息，本就不应被任何缓存留存。
header('Cache-Control: no-store, must-revalidate');
header('Pragma: no-cache');

// 登出（仅接受 POST + CSRF，防登出 CSRF）
if (($_GET['action'] ?? '') === 'logout') {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        exit('Method Not Allowed');
    }
    csrf_verify_or_die();
    if (auth_check()) audit('logout');
    auth_logout();
    redirect('admin.php');
}

// 未登录（或非管理员会话）→ 登录流程
if (!auth_check() || !auth_is_admin()) {
    $error = null;
    $oldUser = '';
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        csrf_verify_or_die();
        $u = trim((string)($_POST['username'] ?? ''));
        if (strlen($u) > 190) $u = substr($u, 0, 190);   // 限长，防未认证写入撑爆 login_attempts / audit_log
        $p = (string)($_POST['password'] ?? '');
        $oldUser = $u;
        $ip = client_ip();
        if (auth_is_locked($ip, $u)) {                     // ① 锁定检查先于验证：防 Argon2id 资源耗尽 + 计时旁路
            $error = '尝试过于频繁，请 15 分钟后再试。';
        } elseif ($u !== '' && auth_verify_credentials($u, $p) && auth_user_role($u) === 'admin') {
            auth_record_attempt($ip, $u, true);
            auth_login_user($u);
            audit('login');
            redirect('admin.php');
        } else {
            auth_record_attempt($ip, $u, false);
            audit('login_failed', 'user', $u);
            $error = '用户名或密码错误';                    // ② 统一模糊错误：不泄露账号是否存在
        }
    }
    include __DIR__ . '/../app/admin/login.php';
    exit;
}

// 已登录 → 模块路由（白名单）
$module = preg_replace('/[^a-z_]/', '', (string)($_GET['m'] ?? 'dashboard'));
$allowed = ['dashboard','news','team','partners','cards','snippets','members','threads','edu_reviews','feedback','password'];
if (!in_array($module, $allowed, true)) $module = 'dashboard';

// ③ 强制改密：初始密码未改前，除改密模块外一律重定向（集中风控，保护所有模块）
if (auth_must_change_password((string)$_SESSION['uid']) && $module !== 'password') {
    flash_set('warn', '首次登录请先修改初始密码。');
    redirect('admin.php?m=password');
}

// 反馈 CSV 导出：须在 HTML 外壳之前输出，避免被顶栏/侧栏包裹
if ($module === 'feedback' && ($_GET['act'] ?? '') === 'export') {
    while (ob_get_level() > 0) { ob_end_clean(); }
    $rows = feedback_list();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="feedback-' . date('Ymd-His') . '.csv"');
    echo "\xEF\xBB\xBF";  // UTF-8 BOM，Excel 中文不乱码
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', '称呼', '联系方式', '内容', '状态', 'IP', '提交时间']);
    foreach ($rows as $r) {
        fputcsv($out, [
            (int)$r['id'], (string)$r['name'], (string)$r['email'],
            (string)$r['message'],
            ($r['status'] ?? '') === 'done' ? '已处理' : '未处理',
            (string)($r['ip'] ?? ''), (string)$r['created_at'],
        ]);
    }
    fclose($out);
    exit;
}

require_once __DIR__ . '/../app/admin/crud.php';   // 通用 CRUD 组件（模块调用 admin_crud）
$moduleFile = __DIR__ . '/../app/admin/' . $module . '.php';

ob_start();                                        // 缓冲输出：模块 POST 写入后可安全重定向（PRG），即便外壳已渲染
include __DIR__ . '/../app/admin/shell.php';       // 外壳开始（顶栏/侧栏/flash）
if (is_file($moduleFile)) {
    include $moduleFile;                           // 模块内容
} else {
    echo '<div class="card"><h3>模块开发中</h3><p>该模块将在后续任务中实现。</p></div>';  // 占位，防缺失 include 致命错误
}
include __DIR__ . '/../app/admin/shell_end.php';   // 外壳闭合
ob_end_flush();                                    // 冲刷缓冲输出
