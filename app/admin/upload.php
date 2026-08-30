<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';

function upload_ext_for_mime(string $mime): ?string {
    return UPLOAD_ALLOWED[$mime] ?? null;
}
function upload_random_name(string $ext): string {
    return bin2hex(random_bytes(16)) . '.' . $ext;
}
/** 处理 $_FILES[$field]：成功返回相对 URL；未上传返回 null；校验失败抛 RuntimeException */
function handle_upload(string $field): ?string {
    if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $f = $_FILES[$field];
    if (is_array($f['error'] ?? null)) throw new RuntimeException('不支持多文件上传');
    if ($f['error'] !== UPLOAD_ERR_OK)          throw new RuntimeException('上传失败(错误码 ' . $f['error'] . ')');
    if ($f['size'] > UPLOAD_MAX_BYTES)          throw new RuntimeException('文件超过 2MB 上限');
    if (!is_uploaded_file($f['tmp_name']))      throw new RuntimeException('非法上传');

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string)$finfo->file($f['tmp_name']);
    $ext = upload_ext_for_mime($mime);
    if ($ext === null)                          throw new RuntimeException('仅支持 JPG/PNG/WebP');
    if (getimagesize($f['tmp_name']) === false) throw new RuntimeException('文件不是有效图片');

    if (!is_dir(UPLOAD_DIR) && !@mkdir(UPLOAD_DIR, 0750, true) && !is_dir(UPLOAD_DIR)) {
        throw new RuntimeException('上传目录不可用');
    }
    $name = upload_random_name($ext);
    $dest = UPLOAD_DIR . '/' . $name;
    if (!move_uploaded_file($f['tmp_name'], $dest)) throw new RuntimeException('保存失败');
    @chmod($dest, 0640);
    return UPLOAD_URL . '/' . $name;
}
