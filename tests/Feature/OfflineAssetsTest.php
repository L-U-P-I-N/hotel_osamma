<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * لا شيء في الواجهة يُطلب من الإنترنت.
 *
 * النظام يعمل على جهاز في الفندق بلا إنترنت، وكانت Tailwind وAlpine
 * وflatpickr وChart.js وخطّ Cairo تُحمَّل من CDN وقت فتح الصفحة: فينقطع
 * الإنترنت فتظهر الشاشة بلا تنسيق ولا تفتح نوافذها ولا يعمل مُنتقي التاريخ.
 *
 * وهذا الاختبار هو ما يمنع عودتها: وسمٌ واحد يُضاف سهواً يُعيد العطل كله،
 * ولا يظهر في أي اختبار آخر لأن كل شيء يعمل ما دام الإنترنت موصولاً.
 */
class OfflineAssetsTest extends TestCase
{
    /** مضيفون لا يجوز أن تُطلب منهم أصول الواجهة. */
    private const FORBIDDEN = [
        'cdn.tailwindcss.com',
        'cdn.jsdelivr.net',
        'unpkg.com',
        'cdnjs.cloudflare.com',
        'code.jquery.com',
        'fonts.googleapis.com',
        'fonts.gstatic.com',
    ];

    public function test_no_blade_view_loads_an_asset_from_the_internet(): void
    {
        $offenders = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            if (!str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $contents = $file->getContents();

            foreach (self::FORBIDDEN as $host) {
                if (str_contains($contents, $host)) {
                    $offenders[] = $file->getRelativePathname() . ' → ' . $host;
                }
            }
        }

        $this->assertSame([], $offenders, "أصولٌ تُطلب من الإنترنت:\n" . implode("\n", $offenders));
    }

    /** والحزمة المحلية مبنيّة فعلاً — وإلا سقطت كل صفحة عند ‎@vite. */
    public function test_the_local_bundle_is_built(): void
    {
        $this->assertFileExists(public_path('build/manifest.json'));
    }

    /**
     * والبناء مرفوعٌ مع المشروع، لا متروكٌ لخطوة بناء عند النشر.
     *
     * كان public/build في .gitignore، فنُشر المشروع بلا manifest.json —
     * و@vite لا يتسامح مع غيابه: ليس تنسيقاً ناقصاً بل خطأ 500 في كل صفحة،
     * فسقط الموقع كلّه. والبناء لا يجري إلا حيث يُستدعى npm صراحةً، ومنصّات
     * النشر قد لا تفعل.
     */
    public function test_the_built_bundle_is_not_ignored_by_git(): void
    {
        $ignore = File::get(base_path('.gitignore'));

        $active = collect(preg_split('/\R/', $ignore))
            ->map(fn (string $line) => trim($line))
            ->filter(fn (string $line) => $line !== '' && !str_starts_with($line, '#'))
            ->filter(fn (string $line) => str_contains($line, 'public/build'));

        $this->assertTrue(
            $active->isEmpty(),
            'public/build مُستثنى في .gitignore — سيُنشر المشروع بلا أصول وتسقط كل صفحة'
        );
    }

    /** والمكتبات الثلاث والخطّ في الحزمة، لا في وسومٍ متفرّقة. */
    public function test_the_bundle_entry_point_pulls_in_every_library(): void
    {
        $entry = File::get(resource_path('js/app.js'));

        foreach (['alpinejs', 'flatpickr', 'chart.js'] as $library) {
            $this->assertStringContainsString($library, $entry, "المكتبة {$library} ليست في الحزمة");
        }

        $this->assertStringContainsString('@fontsource/cairo', File::get(resource_path('css/app.css')));
    }
}
