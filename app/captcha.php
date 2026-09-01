<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

/** 生成验证码答案（去除易混字符），4 位 */
function captcha_generate(): string {
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $s = '';
    for ($i = 0; $i < 4; $i++) $s .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    return $s;
}

/** 答案哈希后存 session（非明文），带过期戳 */
function captcha_store(string $answer): void {
    $_SESSION['__captcha'] = [
        'hash' => hash('sha256', strtolower($answer)),
        'exp'  => time() + CAPTCHA_TTL,
    ];
}

/** 比对：不区分大小写；一次性（无论对错用后即清）；过期即失败 */
function captcha_answer_ok(string $input): bool {
    $c = $_SESSION['__captcha'] ?? null;
    unset($_SESSION['__captcha']);                 // 一次性：先清除，防重放
    if (!is_array($c) || !isset($c['hash'], $c['exp'])) return false;
    if (time() > (int)$c['exp']) return false;     // 过期
    return hash_equals((string)$c['hash'], hash('sha256', strtolower($input)));
}

/** 渲染 PNG（GD）；图片本身不单测 */
function captcha_render_png(string $answer): void {
    $w = 120; $h = 40;
    $im = imagecreatetruecolor($w, $h);
    $bg = imagecolorallocate($im, 245, 245, 250);
    imagefilledrectangle($im, 0, 0, $w, $h, $bg);
    for ($i = 0; $i < 4; $i++) {
        $col = imagecolorallocate($im, random_int(60, 140), random_int(60, 140), random_int(120, 200));
        imagestring($im, 5, 12 + $i * 26, random_int(6, 14), $answer[$i], $col);
    }
    for ($i = 0; $i < 6; $i++) {
        $lc = imagecolorallocate($im, random_int(160, 210), random_int(160, 210), random_int(160, 210));
        imageline($im, random_int(0, $w), random_int(0, $h), random_int(0, $w), random_int(0, $h), $lc);
    }
    header('Content-Type: image/png');
    header('Cache-Control: no-store, must-revalidate');
    imagepng($im);
    imagedestroy($im);
}
