<?php

if (!function_exists('ar_pdf')) {
    function ar_pdf(?string $text): string
    {
        if (empty($text)) return '';

        if (!preg_match('/[\x{0600}-\x{06FF}]/u', $text)) {
            return $text;
        }

        try {
            static $glyphs = null;
            if ($glyphs === null) {
                // Use bundled copy inside the project (no composer dependency needed)
                $localPath = app_path('Libraries/ArPdf/Arabic.php');
                if (!file_exists($localPath)) {
                    return $text;
                }
                set_include_path(get_include_path() . PATH_SEPARATOR . app_path('Libraries/ArPdf'));
                $prev = error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
                require_once $localPath;
                $glyphs = new I18N_Arabic('Glyphs');
                error_reporting($prev);
            }

            $prev = error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
            $result = $glyphs->utf8Glyphs($text);
            error_reporting($prev);
            return $result;
        } catch (\Throwable $e) {
            return $text;
        }
    }
}
