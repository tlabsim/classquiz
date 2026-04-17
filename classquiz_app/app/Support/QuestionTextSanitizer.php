<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

class QuestionTextSanitizer
{
    private const ALLOWED_TAGS = [
        'a',
        'blockquote',
        'br',
        'em',
        'h2',
        'h3',
        'h4',
        'img',
        'li',
        'ol',
        'p',
        'strong',
        'u',
        'ul',
    ];

    private const ALLOWED_ATTRIBUTES = [
        'a' => ['href', 'target', 'rel'],
        'img' => ['src', 'alt', 'title'],
    ];

    public static function sanitize(?string $html): string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return '';
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $internalErrors = libxml_use_internal_errors(true);

        $document->loadHTML(
            '<div>'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING
        );

        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);

        $wrapper = $document->documentElement;

        if (! $wrapper instanceof DOMElement) {
            return e(strip_tags($html));
        }

        self::sanitizeNode($wrapper);

        $cleanHtml = '';

        foreach ($wrapper->childNodes as $child) {
            $cleanHtml .= $document->saveHTML($child);
        }

        $cleanHtml = trim($cleanHtml);

        if (self::isVisuallyEmpty($wrapper)) {
            return '';
        }

        return $cleanHtml;
    }

    private static function sanitizeNode(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE) {
                $tag = strtolower($child->nodeName);

                if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                    self::unwrapNode($child);
                    continue;
                }

                self::sanitizeAttributes($child);
                self::sanitizeNode($child);

                continue;
            }

            if ($child->nodeType === XML_COMMENT_NODE) {
                $node->removeChild($child);
            }
        }
    }

    private static function sanitizeAttributes(DOMNode $node): void
    {
        if (! $node instanceof DOMElement || ! $node->hasAttributes()) {
            return;
        }

        $tag = strtolower($node->tagName);
        $allowedAttributes = self::ALLOWED_ATTRIBUTES[$tag] ?? [];

        foreach (iterator_to_array($node->attributes) as $attribute) {
            $name = strtolower($attribute->nodeName);

            if (! in_array($name, $allowedAttributes, true)) {
                $node->removeAttribute($name);
                continue;
            }

            $value = trim($attribute->nodeValue);

            if (in_array($name, ['href', 'src'], true) && ! self::isSafeUrl($value)) {
                $node->removeAttribute($name);
            }
        }

        if ($tag === 'a' && $node->hasAttribute('href')) {
            $node->setAttribute('target', '_blank');
            $node->setAttribute('rel', 'noopener noreferrer');
        }
    }

    private static function isSafeUrl(string $url): bool
    {
        if ($url === '') {
            return false;
        }

        if (str_starts_with($url, '/')) {
            return true;
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return filter_var($url, FILTER_VALIDATE_URL) !== false;
        }

        return false;
    }

    private static function unwrapNode(DOMNode $node): void
    {
        $parent = $node->parentNode;

        if (! $parent) {
            return;
        }

        while ($node->firstChild) {
            $parent->insertBefore($node->firstChild, $node);
        }

        $parent->removeChild($node);
    }

    private static function isVisuallyEmpty(DOMNode $node): bool
    {
        if ($node instanceof DOMElement && strtolower($node->tagName) === 'img') {
            return false;
        }

        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $text = str_replace("\xc2\xa0", ' ', $child->nodeValue ?? '');

                if (trim($text) !== '') {
                    return false;
                }
            }

            if ($child->nodeType === XML_ELEMENT_NODE && !self::isVisuallyEmpty($child)) {
                return false;
            }
        }

        return true;
    }
}
