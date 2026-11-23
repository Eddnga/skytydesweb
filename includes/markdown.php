<?php
declare(strict_types=1);

/**
 * Small, safe formatter for content written in the dashboard.
 * Everything is HTML-escaped first, so no raw HTML/JS can be injected.
 * Supported: ## Heading, ### Subheading, - bullet, 1. numbered, > quote,
 * **bold**, *italic*, [link text](https://...), blank line = new paragraph.
 */
function is_rich(?string $text): bool
{
    return (bool) preg_match('/^\s*<(p|h[1-6]|ul|ol|blockquote|table|div|br|strong|em|b|i|u|a|span)\b/i', (string) $text);
}

/**
 * Whitelist HTML cleaner for text written in the dashboard's rich editor.
 * Keeps simple formatting only; removes scripts, event handlers, styles, iframes and unsafe links.
 */
function sanitize_html(?string $html): string
{
    $html = trim((string) $html);
    if ($html === '') return '';
    $allowed = [
        'p' => [], 'br' => [], 'h2' => [], 'h3' => [], 'h4' => [], 'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [],
        'ul' => [], 'ol' => [], 'li' => [], 'blockquote' => [], 'a' => ['href', 'target'], 'hr' => [],
        'table' => [], 'thead' => [], 'tbody' => [], 'tr' => [], 'th' => ['colspan', 'rowspan'], 'td' => ['colspan', 'rowspan'],
    ];
    $drop = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'textarea', 'select', 'svg', 'math', 'link', 'meta', 'img', 'video', 'audio', 'noscript', 'template'];
    $map = ['h1' => 'h2', 'h5' => 'h4', 'h6' => 'h4', 'div' => 'p'];

    $doc = new DOMDocument('1.0', 'UTF-8');
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8"?><div id="__root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
    libxml_clear_errors();
    $root = $doc->getElementById('__root');
    if (!$root) return h(strip_tags($html));

    $clean = function (DOMNode $node) use (&$clean, $doc, $allowed, $drop, $map) {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMComment || $child instanceof DOMProcessingInstruction) {
                $node->removeChild($child);
                continue;
            }
            if (!$child instanceof DOMElement) continue;
            $tag = strtolower($child->nodeName);
            if (in_array($tag, $drop, true)) {
                $node->removeChild($child);
                continue;
            }
            if (isset($map[$tag])) {
                $new = $doc->createElement($map[$tag]);
                while ($child->firstChild) $new->appendChild($child->firstChild);
                if ($child->hasAttribute('style')) $new->setAttribute('style', $child->getAttribute('style'));
                $node->replaceChild($new, $child);
                $child = $new;
                $tag = $map[$tag];
            }
            if (!isset($allowed[$tag])) {
                // unknown tag (span, font…): keep its text, drop the tag
                $clean($child);
                while ($child->firstChild) $node->insertBefore($child->firstChild, $child);
                $node->removeChild($child);
                continue;
            }
            // attributes
            $align = null;
            if (in_array($tag, ['p', 'h2', 'h3', 'h4', 'li', 'td', 'th'], true) && preg_match('/text-align:\s*(center|right|justify)/i', $child->getAttribute('style'), $m)) $align = strtolower($m[1]);
            foreach (iterator_to_array($child->attributes) as $attr) {
                if (!in_array(strtolower($attr->name), $allowed[$tag], true)) $child->removeAttribute($attr->name);
            }
            if ($align) $child->setAttribute('class', 'ta-' . $align);
            if ($tag === 'a') {
                $href = trim($child->getAttribute('href'));
                if (!preg_match('#^(https?://|mailto:|tel:|/|\#|[a-z0-9\-_]+\.php)#i', $href)) {
                    $child->removeAttribute('href');
                }
                if ($child->getAttribute('target') === '_blank' || preg_match('#^https?://#i', $href)) {
                    $child->setAttribute('target', '_blank');
                    $child->setAttribute('rel', 'noopener noreferrer');
                } else {
                    $child->removeAttribute('target');
                }
            }
            $clean($child);
        }
    };
    $clean($root);
    $out = '';
    foreach ($root->childNodes as $c) $out .= $doc->saveHTML($c);
    // drop empty paragraphs the editor leaves behind
    $out = preg_replace('#<p>(\s|&nbsp;|<br\s*/?>)*</p>#i', '', $out);
    return trim($out);
}

/** Text for the rich editor: existing plain/markdown text is converted to HTML first. */
function editor_value(?string $text): string
{
    return is_rich($text) ? sanitize_html($text) : md($text);
}

function md(?string $text): string
{
    if (is_rich($text)) return sanitize_html($text);
    $text = str_replace(["\r\n", "\r"], "\n", (string) $text);
    $blocks = preg_split("/\n{2,}/", trim($text));
    $out = '';
    foreach ($blocks as $b) {
        $ls = explode("\n", $b);
        if (preg_match('/^(#{2,3})\s+(.*)$/', $ls[0], $m) && count($ls) === 1) {
            $tag = strlen($m[1]) === 2 ? 'h2' : 'h3';
            $out .= "<$tag>" . md_inline($m[2]) . "</$tag>\n";
            continue;
        }
        if (preg_match('/^[-*]\s+/', $ls[0])) {
            $out .= '<ul>';
            foreach ($ls as $l) $out .= '<li>' . md_inline(preg_replace('/^[-*]\s+/', '', $l)) . '</li>';
            $out .= "</ul>\n";
            continue;
        }
        if (preg_match('/^\d+[.)]\s+/', $ls[0])) {
            $out .= '<ol>';
            foreach ($ls as $l) $out .= '<li>' . md_inline(preg_replace('/^\d+[.)]\s+/', '', $l)) . '</li>';
            $out .= "</ol>\n";
            continue;
        }
        if (preg_match('/^>\s?/', $ls[0])) {
            $out .= '<blockquote>' . md_inline(implode(' ', array_map(fn($l) => preg_replace('/^>\s?/', '', $l), $ls))) . "</blockquote>\n";
            continue;
        }
        $out .= '<p>' . implode('<br>', array_map('md_inline', $ls)) . "</p>\n";
    }
    return $out;
}

function md_inline(string $s): string
{
    $s = h($s);
    $s = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $s);
    $s = preg_replace('/(?<![*\w])\*(?!\s)(.+?)(?<!\s)\*(?![*\w])/', '<em>$1</em>', $s);
    $s = preg_replace_callback('/\[([^\]]+)\]\((https?:\/\/[^\s)]+|\/[^\s)]*)\)/', function ($m) {
        $ext = str_starts_with($m[2], 'http');
        return '<a href="' . $m[2] . '"' . ($ext ? ' target="_blank" rel="noopener noreferrer"' : '') . '>' . $m[1] . '</a>';
    }, $s);
    return $s;
}
