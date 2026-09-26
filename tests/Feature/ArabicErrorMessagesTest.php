<?php
namespace Tests\Feature;

use App\Support\FriendlyError;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * المستخدم موظف استقبال لا مبرمج. كان المشروع بلا ملفات ترجمة ولغته 'en'،
 * فتظهر له رسائل التحقق كرموز خام مثل "validation.required"، وأخطاء قاعدة
 * البيانات كنصّ SQL إنجليزي. كل رسالة يراها يجب أن تصف السبب والإجراء.
 */
class ArabicErrorMessagesTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_validation_messages_are_arabic_not_raw_keys(): void
    {
        $errors = Validator::make([], [
            'guest_id_image' => 'required|image',
            'full_name'      => 'required|string',
            'amount'         => 'required|numeric',
        ])->errors()->all();

        foreach ($errors as $message) {
            $this->assertStringNotContainsString('validation.', $message, 'ظهرت رسالة كرمز خام');
            $this->assertMatchesRegularExpression('/[\x{0600}-\x{06FF}]/u', $message, 'الرسالة ليست بالعربية');
        }

        // واسم الحقل نفسه بالعربية، لا اسمه البرمجي
        $this->assertStringContainsString('صورة هوية النزيل', implode(' ', $errors));
        $this->assertStringNotContainsString('guest_id_image', implode(' ', $errors));
    }

    /** مثال المستخدم: تسجيل دخول نزيل بلا صورة يجب أن يقول له إن الصورة ناقصة. */
    public function test_a_missing_photo_says_so_in_arabic(): void
    {
        $message = Validator::make([], ['id_image' => 'required|image'])->errors()->first();

        $this->assertStringContainsString('صورة الهوية', $message);
        $this->assertStringContainsString('مطلوب', $message);
    }

    public function test_file_size_and_type_messages_explain_the_fix(): void
    {
        $tooLarge = Validator::make(
            ['bank_receipt' => \Illuminate\Http\UploadedFile::fake()->create('r.pdf', 20000)],
            ['bank_receipt' => 'file|max:10240']
        )->errors()->first();

        $this->assertStringContainsString('سند التحويل البنكي', $tooLarge);
        $this->assertStringContainsString('أكبر من المسموح', $tooLarge);
    }

    public static function databaseFailures(): array
    {
        return [
            'تكرار قيمة فريدة' => ["Duplicate entry 'admin' for key 'users.username'", 'مسجّلة من قبل'],
            'حذف سجل مرتبط'    => ['SQLSTATE[23000]: foreign key constraint fails (`reservations`)', 'مرتبط'],
            'حقل إلزامي فارغ'  => ["Column 'amount' cannot be null", 'مطلوب'],
            'نص أطول من الحد'  => ["Data too long for column 'full_name' at row 1", 'أطول من المسموح'],
            'تعذّر الاتصال'    => ['SQLSTATE[HY000] [2002] Connection refused', 'تعذّر الاتصال بقاعدة البيانات'],
        ];
    }

    /** @dataProvider databaseFailures */
    public function test_database_failures_are_explained_in_arabic(string $raw, string $expected): void
    {
        $exception = new QueryException('mysql', 'select 1', [], new \Exception($raw));

        $message = FriendlyError::forDatabase($exception);

        $this->assertStringContainsString($expected, $message);
        $this->assertStringNotContainsString('SQLSTATE', $message, 'تسرّب نصّ تقني للمستخدم');
        $this->assertStringNotContainsString('constraint', $message);
    }

    /** ورسالة التكرار تسمّي الحقل بالعربية حين يمكن استخراجه. */
    public function test_a_duplicate_names_the_field_in_arabic(): void
    {
        $exception = new QueryException('mysql', 'insert', [], new \Exception(
            "Duplicate entry '0412345' for key 'guests.id_number'"
        ));

        $this->assertStringContainsString('رقم الهوية', FriendlyError::forDatabase($exception));
    }

    /** الرفض يعيد الموظف لصفحته مع رسالة تقول له ماذا يفعل، لا مجرد "ممنوع". */
    public function test_a_forbidden_page_explains_what_to_do(): void
    {
        $staff = User::role('receptionist')->firstOrFail();

        $this->actingAs($staff)->get(route('users.index'))
            ->assertRedirect();

        $this->assertStringContainsString('راجع مدير النظام', session('error'));
    }

    public function test_a_forbidden_json_request_answers_in_arabic(): void
    {
        $staff = User::role('receptionist')->firstOrFail();

        $this->actingAs($staff)->getJson(route('users.index'))
            ->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonFragment(['message' => 'ليست لديك صلاحية لهذه العملية — راجع مدير النظام لمنحك الصلاحية.']);
    }

    public function test_every_error_page_is_arabic(): void
    {
        foreach (['403', '404', '419', '429', '500', '503'] as $code) {
            $html = view("errors.{$code}", ['friendly' => null, 'exception' => new \Exception('x')])->render();

            $this->assertMatchesRegularExpression('/[\x{0600}-\x{06FF}]/u', $html, "صفحة {$code} ليست بالعربية");
            $this->assertStringContainsString('dir="rtl"', $html);
        }
    }

    public function test_the_throttle_duration_reads_naturally(): void
    {
        $this->assertSame('30 ثانية', FriendlyError::duration(30));
        $this->assertSame('دقيقة', FriendlyError::duration(60));
        $this->assertSame('دقيقتين', FriendlyError::duration(120));
        $this->assertSame('5 دقائق', FriendlyError::duration(300));
    }
}
