<?php
namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            ['name' => 'ريال يمني',    'symbol' => 'ر.ي', 'code' => 'YER', 'exchange_rate_to_yer' => 1,    'is_primary' => true],
            ['name' => 'ريال سعودي',   'symbol' => 'ر.س', 'code' => 'SAR', 'exchange_rate_to_yer' => 532,  'is_primary' => false],
            ['name' => 'دولار أمريكي', 'symbol' => '$',   'code' => 'USD', 'exchange_rate_to_yer' => 2000, 'is_primary' => false],
        ];

        foreach ($currencies as $data) {
            Currency::updateOrCreate(['code' => $data['code']], $data);
        }
    }
}
