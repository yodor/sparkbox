<?php

/**
 * Utility class for cleaning HTML input to prevent XSS attacks
 * using DOMDocument + DOMXPath with a whitelist-based approach.
 */
class InputSanitizer
{
    /**
     * Default allowed tags (as a concatenated string with angle brackets)
     * @var string
     */
    public static string $defaultAllowedTags =
        "<br><p><a><ul><ol><li><b><u><i><h1><h2><h3><h4><h5><h6><sub><sup><hr><strong><em><span><img><video><iframe>";

    /**
     * Default allowed domains for embedded content (iframes, video src, etc.)
     * @var array<string>
     */
    public static array $allowedDomains = [
        'www.youtube.com',
        'www.youtube-nocookie.com',
        'youtube.com',
        'youtu.be',
    ];

    /**
     * Sanitizes HTML input using DOMDocument and DOMXPath.
     *
     * @param string      $html           Raw (potentially unsafe) HTML
     * @param string|null $allowedTags    Optional override of allowed tags string
     * @param array|null  $allowedDomains Optional override of allowed domains
     * @return string                     Sanitized HTML fragment
     */
    public static function HTML(
        string $html,
        ?string $allowedTags = null,
        ?array $allowedDomains = null
    ): string {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        $allowedTags    = $allowedTags    ?? self::$defaultAllowedTags;
        $allowedDomains = $allowedDomains ?? self::$allowedDomains;

        $allowedElements = self::parseAllowedTags($allowedTags);

        // Full document structure with correct DOCTYPE
        $wrapped = '<!DOCTYPE html>'
            . '<html lang="bg">'
            . '<head><meta charset="UTF-8"></head>'
            . '<body>' . $html . '</body>'           // ← no inner <div>
            . '</html>';

        $doc = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);

        // Load without NOIMPLIED flag (we provide full structure)
        $success = $doc->loadHTML($wrapped, LIBXML_NOBLANKS);

        if (!$success || $doc->documentElement === null) {
            throw new Exception("HTML Parse Error: ".libxml_get_last_error());
        }

        libxml_clear_errors();

        $xpath = new DOMXPath($doc);

        // Allowed attributes per tag
        $allowedAttributes = [
            'a'      => ['href', 'title', 'target', 'rel'],
            'img'    => ['src', 'alt', 'title', 'width', 'height', 'loading'],
            'iframe' => ['src', 'width', 'height', 'frameborder', 'allow', 'allowfullscreen', 'title', 'loading'],
            'video'  => ['src', 'width', 'height', 'controls', 'poster', 'preload', 'title'],
            '*'      => ['class', 'id', 'style'],
        ];

        $dangerousPrefixes = [
            'javascript:', 'vbscript:', 'data:', 'file:', 'about:',
            'on', 'xmlns', 'formaction', 'action', 'background',
        ];

        // Process all elements
        $elements = $xpath->query('//body//*');
        foreach ($elements as $node) {
            if ($node->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            $tag = strtolower($node->nodeName);

            if (!in_array($tag, $allowedElements, true)) {
                // Forbidden tag → replace with text content
                $text = $doc->createTextNode($node->textContent);
                $node->parentNode->replaceChild($text, $node);
                continue;
            }

            // Process attributes
            $attributesToRemove = [];

            foreach ($node->attributes as $attr) {
                $attrName  = strtolower($attr->name);
                $attrValue = trim($attr->value);
                $valueLower = strtolower($attrValue);

                $allowedForTag = ($allowedAttributes[$tag] ?? []) + ($allowedAttributes['*'] ?? []);

                if (!in_array($attrName, $allowedForTag, true)) {
                    $attributesToRemove[] = $attr->name;
                    continue;
                }

                // Dangerous attribute name?
                foreach ($dangerousPrefixes as $prefix) {
                    if (str_starts_with($attrName, $prefix)) {
                        $attributesToRemove[] = $attr->name;
                        continue 2;
                    }
                }

                // Dangerous value (protocol handlers)?
                foreach ($dangerousPrefixes as $prefix) {
                    if (str_starts_with($valueLower, $prefix)) {
                        $attributesToRemove[] = $attr->name;
                        continue 2;
                    }
                }

                // Domain restriction for src/href on media elements
                if (in_array($attrName, ['src', 'href'], true)) {
                    if ($attrValue === '' || $valueLower === '#') {
                        continue;
                    }

                    if (in_array($tag, ['iframe', 'video'], true) && $attrName === 'src') {
                        $host = parse_url($attrValue, PHP_URL_HOST);
                        if ($host === null || !in_array($host, $allowedDomains, true)) {
                            $attributesToRemove[] = $attr->name;
                        }
                    }
                }
            }

            // Apply removals
            foreach ($attributesToRemove as $attrName) {
                $node->removeAttribute($attrName);
            }
        }

        // Extract cleaned content (direct children of <body>)
        $cleanHtml = '';
        $body = $doc->getElementsByTagName('body')->item(0);
        if ($body && $body->hasChildNodes()) {
            foreach ($body->childNodes as $child) {
                $cleanHtml .= $doc->saveHTML($child);
            }
        }

        // Decode entities for readable output
        return html_entity_decode($cleanHtml, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Parses tag string like "<p><br><a>" into array of lowercase tag names
     *
     * @param string $tagString
     * @return array<string>
     */
    private static function parseAllowedTags(string $tagString): array
    {
        preg_match_all('/[a-zA-Z0-9]+/', $tagString, $matches);
        $tags = array_map('strtolower', $matches[0]);
        return array_unique($tags);
    }

    public static function SQL(string $input) : string
    {

        $sql_patterns = [
            '/\b(SELECT|INSERT|UPDATE|DELETE|DROP|ALTER|CREATE|TRUNCATE|RENAME|REPLACE|GRANT|REVOKE)\b/i',
            '/\b(UNION|JOIN|HAVING|ORDER\s+BY|GROUP\s+BY|LIMIT|OFFSET|FETCH|INTO|VALUES|WHERE)\b/i',
            '/\b(0x[0-9a-f]+|0b[01]+)\b/i',
            '/\b(INFORMATION_SCHEMA|SLEEP|BENCHMARK|LOAD_FILE|OUTFILE)\b/i',
            '/--\s?/', '/\/\*.*?\*\//s'
        ];

        $output = $input;
        $max_iterations = 3; // Safety cap to prevent DoS
        $iteration = 0;

        // Keep cleaning until the string stops changing or we hit the cap
        do {
            $before = $output;
            $output = preg_replace($sql_patterns, '', $output);
            $iteration++;
        } while ($output !== $before && $iteration < $max_iterations);

        // Final removal of structural characters
        //$output = str_replace(["'", '"', ";", "\\", "`"], "", $output);

        return trim($output);
    }

    /**
     * Checks if the provided string is suitable as a single-word SQL column name.
     *
     * Validation rules:
     * 1. Must be a non-empty string
     * 2. Must contain exactly one word (no whitespace characters)
     * 3. Must start with a letter or underscore
     * 4. May contain only letters (a-z A-Z), digits (0-9), and underscores
     * 5. Must not match common SQL reserved keywords
     * 6. Length should not exceed 63 characters (PostgreSQL default limit)
     *
     * @param mixed $input The value to validate
     * @return bool
     */
    static public function SafeSQLColumn(string $input): bool
    {

        $s = trim($input);
        if ($s === '') {
            return false;
        }

        // Reasonable length limit (can be adjusted per database)
        if (strlen($s) > 32) {
            return false;
        }

        // No whitespace allowed (ensures exactly one "word")
        if (preg_match('/\s/', $s)) {
            return false;
        }

        // Must match: starts with letter or _, then letters/digits/_
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $s)) {
            return false;
        }

        // Basic list of common reserved words (case-insensitive)
        $reserved = [
            'select', 'insert', 'update', 'delete', 'from', 'where', 'group', 'by',
            'having', 'order', 'limit', 'offset', 'join', 'inner', 'left', 'right',
            'full', 'outer', 'on', 'as', 'and', 'or', 'not', 'null', 'true', 'false',
            'user', 'table', 'index', 'view', 'trigger', 'function', 'column', 'key',
            'default', 'constraint', 'primary', 'foreign', 'unique', 'check'
        ];

        if (in_array(strtolower($s), $reserved, true)) {
            return false;
        }

        return true;
    }
}