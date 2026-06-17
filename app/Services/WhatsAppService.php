<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    public function sendOtp(string $phone, string $otp): bool
    {
        $apiKey  = config('services.callmebot.api_key');
        $message = "فندق أسامة 🏨\nرمز التحقق الخاص بك هو:\n*{$otp}*\nصالح لمدة 10 دقائق.\nلا تشاركه مع أحد.";

        $phone = preg_replace('/[^0-9]/', '', $phone);

        try {
            $response = Http::timeout(15)->get('https://api.callmebot.com/whatsapp.php', [
                'phone'  => $phone,
                'text'   => $message,
                'apikey' => $apiKey,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('WhatsApp OTP failed: ' . $e->getMessage());
            return false;
        }
    }
}
