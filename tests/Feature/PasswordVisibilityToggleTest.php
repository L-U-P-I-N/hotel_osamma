<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * كل حقل كلمة مرور يحمل زر إظهار/إخفاء خاصاً به. الصفحة قد تضمّ ثلاثة حقول
 * (جديدة، تأكيدها، الحالية) فلا يصلح معرّف ثابت واحد — لكلٍّ معرّفه وزرّه.
 */
class PasswordVisibilityToggleTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): User { return User::role('admin')->firstOrFail(); }

    public function test_settings_page_has_a_toggle_for_each_of_its_three_password_fields(): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('settings.index'))
            ->assertOk()
            ->getContent();

        foreach (['password', 'password_confirmation', 'current_password'] as $field) {
            $this->assertMatchesRegularExpression(
                '/id="pw_\w+"[^>]*name="' . $field . '"/',
                $html,
                "الحقل {$field} يجب أن يحمل معرّفاً خاصاً به"
            );
        }

        // ثلاثة أزرار، لكلٍّ معرّف حقلٍ مختلف
        preg_match_all('/data-pw-toggle="(pw_\w+)"/', $html, $toggles);
        $this->assertCount(3, $toggles[1]);
        $this->assertSame($toggles[1], array_unique($toggles[1]), 'المعرّفات يجب أن تكون فريدة');

        // كل زر يشير إلى حقل موجود فعلاً في الصفحة
        foreach ($toggles[1] as $id) {
            $this->assertStringContainsString('id="' . $id . '"', $html);
        }
    }

    /** توجيهات Blade لا تُترجَم داخل سمات المكوّنات، فيجب ألا تظهر حرفياً. */
    public function test_no_raw_blade_directive_leaks_into_the_markup(): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('settings.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('@error(', $html);
        $this->assertStringNotContainsString('@enderror', $html);
    }

    public function test_the_toggle_script_is_included_once_only(): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('settings.index'))
            ->assertOk()
            ->getContent();

        $this->assertSame(1, substr_count($html, "data-pw-toggle]'"),
            'سكربت التبديل يجب أن يُدرَج مرة واحدة مهما تعدّدت الحقول');
    }

    public function test_the_staff_password_reset_field_has_a_toggle(): void
    {
        $this->actingAs($this->admin())
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('data-pw-toggle', false);
    }

    /** الأيقونة لا تُغطّي النص المكتوب: الحقل يحمل حشواً من جهتها. */
    public function test_the_field_reserves_room_for_the_icon(): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('settings.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('padding-right:2.4rem', $html);
    }
}
