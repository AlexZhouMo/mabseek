<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';   // 需要 UPLOAD_URL 判定站内图

const SANITIZE_ALLOWED_TAGS = ['p','h2','h3','strong','b','em','i','u','ul','ol','li','a','img','br','blockquote'];
const SANITIZE_DROP_TAGS    = ['script','style','iframe','object','embed'];

/** 白名单净化富文本 HTML，返回可安全直出的 HTML；依赖 ext-dom。失败/空 → '' */
function sanitize_html(string $html): string {
    $html = trim($html);
    if ($html === '') return '';

    $dom = new DOMDocument('1.0', 'UTF-8');
    // <?xml encoding> 前缀：强制 UTF-8 解析，避免中文被当 Latin-1；NOIMPLIED/NODEFDTD：不注入 html/body/DTD
    $wrapped = '<?xml encoding="UTF-8"?><div>' . $html . '</div>';
    $prev = libxml_use_internal_errors(true);
    $ok = $dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);
    if ($ok === false) return '';

    $root = $dom->getElementsByTagName('div')->item(0);   // 我们包裹的最外层 div
    if ($root === null) return '';

    sanitize_node($root);

    $out = '';
    foreach (iterator_to_array($root->childNodes) as $child) {
        $out .= $dom->saveHTML($child);
    }
    return trim($out);
}

/** 递归净化：自底向上，删除危险容器、解包非白名单元素、清洗白名单元素属性 */
function sanitize_node(DOMNode $node): void {
    foreach (iterator_to_array($node->childNodes) as $child) {
        if ($child instanceof DOMText) continue;               // 文本保留
        if (!($child instanceof DOMElement)) {                 // 注释 / PI 等：删除
            $node->removeChild($child);
            continue;
        }
        $tag = strtolower($child->tagName);

        if (in_array($tag, SANITIZE_DROP_TAGS, true)) {         // 危险容器整体删除
            $node->removeChild($child);
            continue;
        }

        sanitize_node($child);                                  // 先处理子孙

        if (!in_array($tag, SANITIZE_ALLOWED_TAGS, true)) {     // 非白名单：解包
            sanitize_unwrap($child);
            continue;
        }
        sanitize_attrs($child, $tag);                           // 白名单：清洗属性
    }
}

/** 解包：把元素的子节点提到它前面，再删除元素本身 */
function sanitize_unwrap(DOMElement $el): void {
    $parent = $el->parentNode;
    if ($parent === null) return;
    while ($el->firstChild) {
        $parent->insertBefore($el->firstChild, $el);
    }
    $parent->removeChild($el);
}

/** 按标签重建属性白名单：先读需保留的值，删光全部属性，再按需补回 */
function sanitize_attrs(DOMElement $el, string $tag): void {
    if ($tag === 'a') {
        $href = trim($el->getAttribute('href'));
        sanitize_strip_attrs($el);
        if ($href !== '' && preg_match('#^https?://#i', $href)) {
            $el->setAttribute('href', $href);
            $el->setAttribute('rel', 'noopener noreferrer');
        }
    } elseif ($tag === 'img') {
        $src = trim($el->getAttribute('src'));
        $alt = $el->getAttribute('alt');
        sanitize_strip_attrs($el);
        if ($src !== '' && str_starts_with($src, UPLOAD_URL . '/') && strpos($src, '..') === false) {   // 仅站内上传图，且无路径穿越
            $el->setAttribute('src', $src);
            $el->setAttribute('alt', $alt);
        } else {
            $el->parentNode?->removeChild($el);                          // 外链图整体删除
        }
    } else {
        sanitize_strip_attrs($el);                                       // 其余白名单元素：删光属性
    }
}

/** 删除元素上所有属性 */
function sanitize_strip_attrs(DOMElement $el): void {
    foreach (iterator_to_array($el->attributes) as $attr) {
        $el->removeAttribute($attr->name);
    }
}
