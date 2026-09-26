<?php
namespace App\Support;

use App\Models\PdfTemplate;

/**
 * توليد PDF من قالب صمّمه المدير، داخل نفس ترويسة الفندق الرسمية
 * المستعملة في بقية التصديرات كي لا تفقد المستندات هويتها الموحَّدة.
 */
class TemplatePdf
{
    public static function render(PdfTemplate $template, array $viewData): \Barryvdh\DomPDF\PDF
    {
        $html = self::html($template, $viewData);

        return \Barryvdh\DomPDF\Facade\Pdf::loadHtml(pdf_arabic_html($html));
    }

    /**
     * ناتج القالب داخل صفحة كاملة.
     *
     * $forBrowser للمعاينة داخل المتصفح: شعار الفندق يُخزَّن كمسار ملف على
     * الخادم، وهو ما يفهمه dompdf ولا يفهمه المتصفح، فيُضمَّن كـ data URI.
     */
    public static function html(PdfTemplate $template, array $viewData, bool $forBrowser = false): string
    {
        $docTitle = $template->doc_title ?: ReportRegistry::label($template->report_key);
        $data     = TemplateData::fromViewData($viewData, $docTitle);
        $body     = (new TemplateRenderer())->render($template->body, $data);

        $html = view('pdf_templates.shell', [
            'body'     => $body,
            'docTitle' => $docTitle,
            'docMeta'  => now()->format('Y/m/d'),
        ])->render();

        return $forBrowser ? self::inlineLocalImages($html) : $html;
    }

    /** تحويل مسارات الصور المحلية إلى data URI كي تظهر في المعاينة. */
    private static function inlineLocalImages(string $html): string
    {
        return preg_replace_callback(
            '/src="(\/[^"]+\.(?:png|jpe?g|gif|webp|svg))"/i',
            function (array $match) {
                $path = $match[1];

                if (!is_file($path) || !is_readable($path) || filesize($path) > 2_000_000) {
                    return 'src=""';
                }

                $mime = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
                    'png'  => 'image/png',
                    'gif'  => 'image/gif',
                    'webp' => 'image/webp',
                    'svg'  => 'image/svg+xml',
                    default => 'image/jpeg',
                };

                return 'src="data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($path)) . '"';
            },
            $html
        ) ?? $html;
    }
}
