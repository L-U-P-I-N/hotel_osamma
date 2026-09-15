<?php
namespace Tests\Unit;

use Tests\TestCase;

/**
 * مكتبة تحويل العربية لأشكال العرض (ar-php) تحوّل أي علامة ترقيم خارج ASCII
 * والعربية إلى تنوين فتح "ً"، فكانت الشرطة الطويلة — المستعملة في عشرات قوالب
 * التصدير — تظهر كرمز غريب داخل النص العربي.
 */
class ArabicPdfPunctuationTest extends TestCase
{
    public static function manglingPunctuation(): array
    {
        return [
            'شرطة طويلة'   => ['الغرفة 207 — مقيم',        '-'],
            'شرطة متوسطة'  => ['الغرفة 207 – مقيم',        '-'],
            'نقطة وسطى'    => ['أحمد · مقيم',              '-'],
            'علامة مئوية'  => ['نسبة 50٪ من الإجمالي',     '%'],
            'قوسان عربيان' => ['قال «مرحبا» له',           '"'],
        ];
    }

    /** @dataProvider manglingPunctuation */
    public function test_punctuation_survives_arabic_shaping(string $text, string $expected): void
    {
        $shaped = ar_pdf($text);

        $this->assertStringContainsString($expected, $shaped);
        $this->assertStringNotContainsString('ً', $shaped, 'ظهر تنوين فتح مكان علامة الترقيم');
    }

    public function test_plain_latin_text_is_left_untouched(): void
    {
        $this->assertSame('Room 207 — occupied', ar_pdf('Room 207 — occupied'));
    }
}
