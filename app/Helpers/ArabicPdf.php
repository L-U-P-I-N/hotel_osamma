<?php

if (!function_exists('ar_pdf')) {
    /**
     * Reshape Arabic text for DomPDF rendering.
     * DomPDF has no OpenType shaping engine, so Arabic letters appear disconnected.
     * This converts Unicode logical-order Arabic to visual-order presentation forms
     * that any font with Arabic Presentation Forms block can render correctly.
     */
    function ar_pdf(?string $text): string
    {
        if (empty($text)) return '';

        // Only reshape if text contains Arabic characters
        if (!preg_match('/[\x{0600}-\x{06FF}]/u', $text)) {
            return $text;
        }

        static $glyphs = null;
        if ($glyphs === null) {
            set_include_path(get_include_path() . PATH_SEPARATOR . base_path('vendor/ar-php/ar-php'));
            // Suppress deprecation warnings from the older ar-php library (utf8_encode deprecated in PHP 8.2)
            $prev = error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
            require_once base_path('vendor/ar-php/ar-php/I18N/Arabic.php');
            $glyphs = new I18N_Arabic('Glyphs');
            error_reporting($prev);
        }

        $prev = error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
        $result = $glyphs->utf8Glyphs($text);
        error_reporting($prev);

        return $result;
    }
}
