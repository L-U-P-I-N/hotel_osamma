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
                $arabicPhpPath = base_path('vendor/ar-php/ar-php/I18N/Arabic.php');
                if (!file_exists($arabicPhpPath)) {
                    return $text;
                }
                set_include_path(get_include_path() . PATH_SEPARATOR . base_path('vendor/ar-php/ar-php'));
                $prev = error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
                require_once $arabicPhpPath;
                $glyphs = new I18N_Arabic('Glyphs');
                error_reporting($prev);
            }

            $prev = error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
            // max_chars=5000 prevents internal line-breaking; hindo=false keeps Western numerals
            $result = $glyphs->utf8Glyphs($text, 5000, false);
            error_reporting($prev);
            return $result;
        } catch (\Throwable $e) {
            return $text;
        }
    }
}

if (!function_exists('pdf_arabic_html')) {
    /**
     * Post-process rendered HTML so dompdf can display Arabic text correctly.
     *
     * DomPDF has no OpenType shaping engine, so raw Unicode Arabic produces
     * disconnected / reversed glyphs. This function converts Arabic Unicode to
     * Arabic Presentation Forms (pre-shaped, visually ordered) via ar-php's
     * Glyphs class. Style and script blocks are left untouched.
     */
    function pdf_arabic_html(string $html): string
    {
        // Stash <style> and <script> blocks so we never modify their content.
        $stash = [];
        $idx   = 0;
        $html  = preg_replace_callback(
            '/<(style|script)[^>]*>.*?<\/\1>/is',
            function ($m) use (&$stash, &$idx) {
                $key          = "\x00STASH{$idx}\x00";
                $stash[$key]  = $m[0];
                $idx++;
                return $key;
            },
            $html
        );

        // Apply ar_pdf() to every text node that contains at least one Arabic character.
        $html = preg_replace_callback(
            '/(>)([^<>]*[\x{0600}-\x{06FF}][^<>]*)(<)/u',
            fn ($m) => $m[1] . ar_pdf($m[2]) . $m[3],
            $html
        );

        // Restore stashed blocks.
        foreach ($stash as $key => $block) {
            $html = str_replace($key, $block, $html);
        }

        return $html;
    }
}

if (!function_exists('pdf_load_view')) {
    /**
     * Drop-in replacement for Pdf::loadView() that fixes Arabic text rendering.
     * Returns the same \Barryvdh\DomPDF\PDF object so callers can chain
     * ->setPaper(), ->stream(), etc. as before.
     */
    function pdf_load_view(string $view, array $data = []): \Barryvdh\DomPDF\PDF
    {
        $html = view($view, $data)->render();
        $html = pdf_arabic_html($html);
        return \Barryvdh\DomPDF\Facade\Pdf::loadHtml($html);
    }
}
