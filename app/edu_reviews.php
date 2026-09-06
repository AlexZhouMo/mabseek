<?php
declare(strict_types=1);

/**
 * 往期回顾字段派生：
 *   - cover 为空 → 从已净化正文 HTML 提取第一张 <img> 的 src（无图则留空，前台用占位）。
 *   - summary 为空 → 正文纯文本前 30 字 + '…'（不足 30 字不加省略号）。
 * 输入 $data 的 body 应已经过 sanitize_html()（img 仅站内上传图）。
 */
function edu_review_derive(array $data): array
{
    $body = (string)($data['body'] ?? '');

    if (trim((string)($data['cover'] ?? '')) === '') {
        $data['cover'] = edu_review_first_image($body);
    }
    if (trim((string)($data['summary'] ?? '')) === '') {
        $data['summary'] = edu_review_summary($body);
    }
    return $data;
}

/** 提取 HTML 中第一张 <img> 的 src；无则返回空串。 */
function edu_review_first_image(string $html): string
{
    if (preg_match('/<img\b[^>]*\bsrc\s*=\s*(["\'])(.*?)\1/i', $html, $m)) {
        return trim($m[2]);
    }
    return '';
}

/** 正文纯文本前 30 字摘要；超过则加省略号。多字节安全。 */
function edu_review_summary(string $html): string
{
    $text = trim(preg_replace('/\s+/u', ' ', strip_tags($html)));
    if ($text === '') return '';
    if (mb_strlen($text) <= 30) return $text;
    return mb_substr($text, 0, 30) . '…';
}
