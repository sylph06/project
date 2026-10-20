<?php

function meeting_minutes_sanitize_html($html) {
    if (!class_exists('DOMDocument')) {
        throw new RuntimeException('The PHP DOM extension is required to sanitize meeting minutes.');
    }

    $document = new DOMDocument('1.0', 'UTF-8');
    $previous = libxml_use_internal_errors(true);
    $loaded = $document->loadHTML(
        '<!DOCTYPE html><html><body><div id="minutes-root">' . $html . '</div></body></html>',
        LIBXML_NONET | LIBXML_HTML_NODEFDTD
    );
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    if (!$loaded) throw new RuntimeException('Could not parse the meeting minutes.');

    $xpath = new DOMXPath($document);
    $root = $xpath->query('//*[@id="minutes-root"]')->item(0);
    if (!$root) throw new RuntimeException('Could not parse the meeting minutes.');

    $allowed = ['p', 'br', 'b', 'strong', 'i', 'em', 'u', 'ul', 'ol', 'li', 'a'];
    $dropContents = ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math', 'template'];
    $sanitizeChildren = function ($parent) use (&$sanitizeChildren, $allowed, $dropContents) {
        foreach (iterator_to_array($parent->childNodes) as $child) {
            if ($child->nodeType === XML_COMMENT_NODE) {
                $parent->removeChild($child);
                continue;
            }
            if ($child->nodeType !== XML_ELEMENT_NODE) continue;

            $tag = strtolower($child->nodeName);
            if (in_array($tag, $dropContents, true) || $tag === 'img' || $tag === 'video' || $tag === 'audio') {
                $parent->removeChild($child);
                continue;
            }

            $sanitizeChildren($child);
            if (!in_array($tag, $allowed, true)) {
                while ($child->firstChild) $parent->insertBefore($child->firstChild, $child);
                $parent->removeChild($child);
                continue;
            }

            $href = null;
            if ($tag === 'a' && $child->hasAttribute('href')) {
                $candidate = trim($child->getAttribute('href'));
                $parts = parse_url($candidate);
                $scheme = is_array($parts) ? strtolower((string)($parts['scheme'] ?? '')) : '';
                if (in_array($scheme, ['http', 'https', 'mailto'], true)) $href = $candidate;
            }
            while ($child->attributes->length) {
                $child->removeAttributeNode($child->attributes->item(0));
            }
            if ($tag === 'a' && $href !== null) {
                $child->setAttribute('href', $href);
                $child->setAttribute('rel', 'noopener noreferrer');
            }
        }
    };
    $sanitizeChildren($root);

    $result = '';
    foreach (iterator_to_array($root->childNodes) as $child) {
        $result .= $document->saveHTML($child);
    }
    return $result;
}

function meeting_minutes_text_length($html) {
    $document = new DOMDocument('1.0', 'UTF-8');
    $previous = libxml_use_internal_errors(true);
    $document->loadHTML(
        '<!DOCTYPE html><html><body><div id="minutes-text">' . $html . '</div></body></html>',
        LIBXML_NONET | LIBXML_HTML_NODEFDTD
    );
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    $xpath = new DOMXPath($document);
    $root = $xpath->query('//*[@id="minutes-text"]')->item(0);
    $text = $root ? $root->textContent : '';
    $count = preg_match_all('/./us', $text, $matches);
    if ($count === false) throw new RuntimeException('Meeting minutes contain invalid text encoding.');
    return $count;
}

function meeting_minutes_within_limit($html) {
    return meeting_minutes_text_length($html) <= 20000;
}

function meeting_minutes_role_can_manage($role) {
    return in_array($role, ['SK Secretary', 'SK Chairman'], true);
}

function meeting_minutes_generic_fields_match($existing, $incoming) {
    $current = [
        'minutes' => is_string($existing['minutes'] ?? null) ? $existing['minutes'] : '',
        'minutesStatus' => ($existing['minutesStatus'] ?? '') === 'Approved' ? 'Approved' : 'Draft',
        'minutesUpdatedBy' => is_string($existing['minutesUpdatedBy'] ?? null) ? $existing['minutesUpdatedBy'] : '',
        'minutesUpdatedAt' => is_string($existing['minutesUpdatedAt'] ?? null) ? $existing['minutesUpdatedAt'] : '',
    ];
    foreach ($current as $field => $value) {
        if (array_key_exists($field, $incoming) && $incoming[$field] !== $value) return false;
    }
    return true;
}

function meeting_minutes_action_allowed($role, $action, $status) {
    $chairman = $role === 'SK Chairman';
    $minutesOfficer = $chairman || $role === 'SK Secretary';
    if ($action === 'save') return $minutesOfficer && $status === 'Draft';
    if ($action === 'approve') return $chairman && $status === 'Draft';
    if ($action === 'reopen') return $chairman && $status === 'Approved';
    return false;
}
