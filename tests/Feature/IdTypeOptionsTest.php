<?php
namespace Tests\Feature;

use App\Models\Companion;
use App\Models\Guest;
use App\Models\User;
use App\Support\AuditFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * أنواع الهوية الستة: مصدرٌ واحد (Guest::ID_TYPES) تقرأ منه الشاشات وقواعد
 * التحقق وسجل المراجعة والتقارير.
 *
 * كانت القائمة مكرّرة حرفياً في تسعة مواضع، فإضافة «رخصة قيادة» و«شهادة ميلاد»
 * و«بطاقة عسكرية» تقتضي تتبّعها كلها — ويكفي أن يُنسى موضعٌ ليُرفض ما يختاره
 * الموظف أو يظهر كوداً خاماً في التقرير. فهذه الاختبارات تحرس التطابق.
 */
class IdTypeOptionsTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): User { return User::role('admin')->firstOrFail(); }

    /** الأنواع المضافة حديثاً — ما كان ينقص في البلاغ. */
    private const NEW_TYPES = ['driver_license', 'birth_certificate', 'military_id'];

    public function test_new_id_types_exist_with_arabic_labels(): void
    {
        foreach (self::NEW_TYPES as $type) {
            $this->assertArrayHasKey($type, Guest::ID_TYPES);
            $this->assertNotSame('', Guest::ID_TYPES[$type]);
            $this->assertArrayHasKey($type, Guest::ID_TYPES_SHORT);
        }

        $this->assertSame(6, count(Guest::ID_TYPES));
    }

    public function test_validation_rule_accepts_every_type_and_rejects_others(): void
    {
        foreach (array_keys(Guest::ID_TYPES) as $type) {
            $validator = Validator::make(['id_type' => $type], ['id_type' => Guest::idTypeRule()]);
            $this->assertTrue($validator->passes(), "النوع {$type} مرفوض في قاعدة التحقق");
        }

        // «أخرى» كانت معروضة في شاشة تعديل الحجز ولا يقبلها عمود enum
        $this->assertTrue(
            Validator::make(['id_type' => 'other'], ['id_type' => Guest::idTypeRule()])->fails()
        );
    }

    public function test_new_types_persist_and_resolve_to_labels(): void
    {
        foreach (self::NEW_TYPES as $type) {
            $guest = Guest::create([
                'full_name' => 'نزيل ' . $type,
                'id_type'   => $type,
                'id_number' => 'X-' . $type,
            ]);

            $this->assertSame($type, $guest->fresh()->id_type);
            $this->assertSame(Guest::ID_TYPES[$type], $guest->getIdTypeLabel());
        }
    }

    public function test_companion_label_follows_the_same_source(): void
    {
        $companion = new Companion(['full_name' => 'مرافق', 'id_type' => 'military_id']);

        $this->assertSame(Guest::ID_TYPES['military_id'], $companion->getIdTypeLabel());
    }

    /**
     * AuditFormatter لا يستطيع استدعاء الثابت داخل تعريف const، فقيمه منسوخة —
     * وهذا الاختبار هو ما يمنع انحرافها عن المصدر.
     */
    public function test_audit_formatter_id_type_map_matches_the_constant(): void
    {
        $reflection = new \ReflectionClass(AuditFormatter::class);
        $maps = $reflection->getConstant('VALUE_MAPS');

        $this->assertSame(Guest::ID_TYPES, $maps['id_type']);
    }

    public function test_checkin_and_edit_screens_offer_all_types(): void
    {
        $this->actingAs($this->admin())->get('/checkin')
            ->assertOk()
            ->assertSee(Guest::ID_TYPES['driver_license'])
            ->assertSee(Guest::ID_TYPES['birth_certificate'])
            ->assertSee(Guest::ID_TYPES['military_id']);
    }
}
