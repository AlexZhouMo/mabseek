<?php
// 课程视频上传：独立于图片上传（大小上限 50MB，存 assets/videos/，路径写入 snippet edu.video.src）。
// 由 admin.php include（在 shell.php 的 ob_start 之后）；POST 处理上传，GET 渲染当前视频与上传表单。
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    while (ob_get_level() > 0) { ob_end_clean(); }
    if (($_POST['action'] ?? '') === 'set_iframe') {
        csrf_verify_or_die();
        $url = trim((string)($_POST['iframe_url'] ?? ''));
        $ok = $url === '' || (
            filter_var($url, FILTER_VALIDATE_URL)
            && (str_starts_with($url, 'https://')
                || str_starts_with($url, 'http://')
                || str_starts_with($url, '//'))
        );
        if (!$ok) {
            flash_set('error', '外链嵌入 URL 格式非法（仅支持 http/https/协议相对）');
        } else {
            Snippets::set('edu.video.iframe_url', $url, 'education',
                '视频外链嵌入 URL（优先于本地视频）', 'text');
            audit('update', 'snippet', 'edu.video.iframe_url');
            flash_set('ok', $url === '' ? '已清空外链（将回退本地视频）' : '外链已保存');
        }
        redirect('admin.php?m=edu_video');
    }
    csrf_verify_or_die();
    try {
        $f = $_FILES['video'] ?? null;
        if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) throw new RuntimeException('未选择文件');
        if (is_array($f['error'])) throw new RuntimeException('不支持多文件');
        if ($f['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('上传失败(错误码 ' . $f['error'] . ')');
        if ($f['size'] > VIDEO_MAX_BYTES) throw new RuntimeException('文件超过 50MB 上限');
        if (!is_uploaded_file($f['tmp_name'])) throw new RuntimeException('非法上传');
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string)$finfo->file($f['tmp_name']);
        $ext = VIDEO_ALLOWED[$mime] ?? null;
        if ($ext === null) throw new RuntimeException('仅支持 MP4 / WebM');
        if (!is_dir(VIDEO_DIR) && !@mkdir(VIDEO_DIR, 0750, true) && !is_dir(VIDEO_DIR)) throw new RuntimeException('视频目录不可用');
        $name = bin2hex(random_bytes(12)) . '.' . $ext;
        $dest = VIDEO_DIR . '/' . $name;
        if (!move_uploaded_file($f['tmp_name'], $dest)) throw new RuntimeException('保存失败');
        @chmod($dest, 0640);
        Snippets::set('edu.video.src', VIDEO_URL . '/' . $name, 'education', '课程视频文件路径（后台上传后自动填入）', 'text');
        audit('update', 'snippet', 'edu.video.src');
        flash_set('ok', '课程视频已上传');
    } catch (\RuntimeException $ex) {
        flash_set('error', $ex->getMessage());
    }
    redirect('admin.php?m=edu_video');
}
$cur = Snippets::get('edu.video.src');
?>
<div class="card">
  <div class="page-head"><h3>课程视频</h3></div>
<?php if ($cur !== ''): ?>
  <video controls preload="metadata" style="width:100%;max-width:640px;border-radius:8px;background:#000"><source src="<?= e($cur) ?>"></video>
  <p style="color:var(--a-ink-3);margin-top:8px">当前视频：<?= e($cur) ?></p>
<?php else: ?>
  <p style="color:var(--a-ink-3)">尚未上传课程视频。</p>
<?php endif; ?>
  <form method="post" action="admin.php?m=edu_video" style="margin-top:16px">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="set_iframe">
    <div class="field">
      <label class="field-label">外链嵌入 URL（优先于本地视频；留空回退）</label>
      <input class="input" type="url" name="iframe_url" value="<?= e(Snippets::get('edu.video.iframe_url')) ?>" placeholder="//player.bilibili.com/player.html?bvid=BVxxx 或 https://www.youtube.com/embed/xxx">
      <div class="field-hint">仅填 URL，非 HTML 片段。示例：Bilibili 分享→嵌入代码里的 //player.bilibili.com/... URL；YouTube 用 embed URL。仅支持 http/https/协议相对。</div>
    </div>
    <div class="form-actions"><button class="abtn abtn-primary" type="submit">保存外链</button></div>
  </form>
  <form method="post" action="admin.php?m=edu_video" enctype="multipart/form-data" style="margin-top:16px">
    <?= csrf_field() ?>
    <div class="field">
      <label class="field-label">上传新视频（替换当前）</label>
      <input type="file" name="video" accept="video/mp4,video/webm" required>
      <div class="field-hint">支持 MP4 / WebM，≤ 50MB。上传后自动应用到教育页。</div>
    </div>
    <div class="form-actions"><button class="abtn abtn-primary" type="submit">上传</button></div>
  </form>
</div>
