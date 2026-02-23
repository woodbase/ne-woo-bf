<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('nebf_clean_product_name')) {
    /**
     * Remove brand prefix from product name when separate-brand setting is enabled,
     * and trim leading/trailing punctuation around the final name.
     */
    function nebf_clean_product_name(string $name, string $brand): string
    {
        $name = trim($name);
        $brand = trim($brand);

        if ($name === '') {
            return '';
        }

        $separate_brand = (bool) get_option('nebf_separate_brand', 0);
        if (!$separate_brand || $brand === '') {
            return $name;
        }

        $clean_name = $name;
        $brand_pattern = preg_quote($brand, '/');

        // Allow punctuation/symbols around a leading brand match.
        $prefixed_pattern = '/^\s*[\p{P}\p{S}]*\s*' . $brand_pattern . '(?:\s*[\p{P}\p{S}]*)?/iu';
        $result = preg_replace($prefixed_pattern, '', $clean_name, 1);
        if (is_string($result)) {
            $clean_name = $result;
        }

        // Remove punctuation/symbols only from beginning and end after split.
        $trimmed_edges = preg_replace('/^[\p{P}\p{S}\s]+|[\p{P}\p{S}\s]+$/u', '', $clean_name);
        if (is_string($trimmed_edges)) {
            $clean_name = $trimmed_edges;
        }

        return trim($clean_name);
    }
}
