<?php
include_once("forms/SEOInputGroup.php");

/**
 * The SEO values of a row (seo_title, seo_description - SEOInputGroup) with their fallbacks: the page reads them
 * the same way for every table that has them
 */
final class SEOData
{
    private function __construct()
    {
    }

    /**
     * The SEO title of '$row', else '$fallback' (the name of the row)
     * @param array $row
     * @param string $fallback
     * @return string
     */
    public static function Title(array $row, string $fallback = "") : string
    {
        $value = trim((string)($row[SEOInputGroup::TITLE] ?? ""));
        return ($value !== "") ? $value : $fallback;
    }

    /**
     * The SEO description of '$row', else '$fallback' (the text of the row)
     * @param array $row
     * @param string $fallback
     * @return string
     */
    public static function Description(array $row, string $fallback = "") : string
    {
        $value = trim((string)($row[SEOInputGroup::DESCRIPTION] ?? ""));
        return ($value !== "") ? $value : $fallback;
    }
}
