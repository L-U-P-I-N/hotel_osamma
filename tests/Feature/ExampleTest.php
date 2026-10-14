<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * الجذر يُحوّل إلى الدخول — لا شاشة عامة في هذا النظام.
 *
 * كان هذا اختبار Laravel الافتراضي يطلب 200 من «/»، فيفشل دائماً منذ أول يوم
 * (الجذر يُحوّل). وفاشلٌ دائمٌ لا يُقرأ: يصير سطراً أحمر يُتخطّى بالعين،
 * فيستر فشلاً حقيقياً يظهر بجواره.
 */
class ExampleTest extends TestCase
{
    public function test_the_root_redirects_a_guest_to_the_login_screen(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }
}
