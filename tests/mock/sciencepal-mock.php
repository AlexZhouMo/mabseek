<?php
declare(strict_types=1);
// SciencePal mock server（仅供本地测试）。启动：php -S localhost:8899 tests/mock/sciencepal-mock.php
// 已开通邮箱存临时文件，跨请求持久。识别 X-Partner-Key；未匹配则 401。
// "exists@" 前缀邮箱视为 SciencePal 已存在账号（非本接口开通）。

const MOCK_KEY = 'c9d2e6a4-3f7b-4c1a-8e5d-6a2b9f0c4d71';   // 与测试用 key 一致
$store = sys_get_temp_dir() . '/scp-mock-created.json';

function mock_created_load(string $f): array { return is_file($f) ? (json_decode((string)file_get_contents($f), true) ?: []) : []; }
function mock_created_save(string $f, array $d): void { file_put_contents($f, json_encode($d)); }
function send(int $code, array $body): void { http_response_code($code); header('Content-Type: application/json'); echo json_encode($body, JSON_UNESCAPED_UNICODE); exit; }

$key = $_SERVER['HTTP_X_PARTNER_KEY'] ?? '';
if ($key !== MOCK_KEY) send(401, ['error' => 'invalid partner key']);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path   = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$in     = json_decode((string)file_get_contents('php://input'), true) ?: [];
$created = mock_created_load($store);

// 路由按 path 尾段匹配，兼容 /api/partner 前缀
if ($method === 'POST' && preg_match('#/users$#', $path)) {
    $email = (string)($in['email'] ?? '');
    $pass  = (string)($in['password'] ?? '');
    if (mb_strlen($pass) < 6) send(400, ['error' => 'password too short']);
    if (str_starts_with($email, 'exists@')) send(200, ['status' => 'exists', 'agent_granted' => true]);
    $created[$email] = true; mock_created_save($store, $created);
    send(200, ['status' => 'created', 'user_id' => 'mock-' . substr(md5($email), 0, 8), 'agent_granted' => true]);
}
if ($method === 'PUT' && preg_match('#/users/password$#', $path)) {
    $email = (string)($in['email'] ?? '');
    if (str_starts_with($email, 'exists@')) send(403, ['error' => 'not provisioned via partner api']);
    if (empty($created[$email])) send(404, ['error' => 'user not found']);
    send(200, ['status' => 'updated']);
}
if ($method === 'POST' && preg_match('#/sso-link$#', $path)) {
    $email = (string)($in['email'] ?? '');
    if (str_starts_with($email, 'exists@') || empty($created[$email])) send(400, ['error' => 'cannot sign for this account']);
    $rp = (string)($in['return_path'] ?? '/dashboard');
    send(200, ['redirect_url' => 'https://sciencepal.ai/auth/partner?token_hash=mock&type=magiclink&returnUrl=' . rawurlencode($rp)]);
}
send(404, ['error' => 'no route']);
