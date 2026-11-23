<?php
declare(strict_types=1);

/**
 * Small, safe formatter for content written in the dashboard.
 * Everything is HTML-escaped first, so no raw HTML/JS can be injected.
 * Supported: ## Heading, ### Subheading, - bullet, 1. numbered, > quote,
 * **bold**, *italic*, [link text](https://...), blank line = new paragraph.
 */
function md(?string $text): string
{
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
