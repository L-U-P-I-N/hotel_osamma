<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\Payment;
use App\Models\PaymentAccount;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * الصناديق والحسابات البنكية: إدارتها ومعرفة رصيد كل واحد على حِدة.
 *
 * الرصيد يُقرأ من دفتر الأستاذ لا من جمع الدفعات، فيشمل كل حركة تمسّ الوعاء
 * (قبض، صرف، تسوية، استرجاع) ولا ينحرف عن الميزان.
 */
class PaymentAccountController extends Controller
{
    public function index(Request $request)
    {
        $accounts = PaymentAccount::with('ledgerAccount')->ordered()->get();

        // حركة الفترة لكل وعاء — تُقرأ من القيود لا من الدفعات وحدها
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to   = $request->input('to', now()->toDateString());

        $movements = DB::table('journal_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->whereIn('journal_lines.account_code', $accounts->pluck('account_code'))
            ->whereDate('journal_entries.entry_date', '>=', $from)
            ->whereDate('journal_entries.entry_date', '<=', $to)
            ->groupBy('journal_lines.account_code')
            ->select(
                'journal_lines.account_code',
                DB::raw('COALESCE(SUM(journal_lines.debit), 0) as total_in'),
                DB::raw('COALESCE(SUM(journal_lines.credit), 0) as total_out')
            )
            ->get()
            ->keyBy('account_code');

        $totalOnHand = $accounts->where('is_active', true)->sum(fn (PaymentAccount $a) => $a->balance);

        return view('payment-accounts.index', compact('accounts', 'movements', 'from', 'to', 'totalOnHand'));
    }

    public function store(Request $request)
    {
        $validated = $this->withColumnDefaults($this->validatePayload($request));

        // الإنشاء خطوتان — حساب في الشجرة ثم الوعاء — فيلزم أن تنجحا معاً أو
        // تفشلا معاً. بدون المعاملة كان فشل الخطوة الثانية يترك حساباً يتيماً
        // في دليل الحسابات بينما تظهر للمستخدم رسالة خطأ، فيظن أن شيئاً لم يُنشأ.
        [$account, $ledger] = DB::transaction(static function () use ($validated) {
            // حساب الوعاء في شجرة USALI يُنشأ آلياً تحت أبيه الصحيح، فلا يُضطر
            // المستخدم لتحرير دليل الحسابات كلما فتح حساباً بنكياً جديداً
            $ledger = PaymentAccount::createLedgerAccount($validated['type'], $validated['name']);

            $account = PaymentAccount::create($validated + [
                'account_code' => $ledger->code,
                'is_active'    => true,
            ]);

            return [$account, $ledger];
        });

        $this->enforceSingleDefault($account);

        AuditLogService::log('create', $account, null, $account->toArray(), auth()->user());

        return back()->with('success', "تمت إضافة «{$account->name}» وربطها بالحساب {$ledger->code}.");
    }

    public function update(Request $request, PaymentAccount $paymentAccount)
    {
        $validated = $this->withColumnDefaults($this->validatePayload($request, $paymentAccount));
        $old = $paymentAccount->toArray();

        // النوع لا يُغيَّر بعد الإنشاء: تغييره ينقل الوعاء إلى فرع آخر من الشجرة
        // بينما قيوده القديمة باقية في فرعه الأول، فينكسر الرصيد.
        unset($validated['type']);

        $paymentAccount->update($validated);
        $this->enforceSingleDefault($paymentAccount);

        AuditLogService::log('update', $paymentAccount, $old, $paymentAccount->toArray(), auth()->user());

        return back()->with('success', 'تم تحديث بيانات الوعاء المالي.');
    }

    /** التعطيل لا الحذف: وعاءٌ له قيود لا يُحذف وإلا انكسر أثر تلك القيود. */
    public function toggle(PaymentAccount $paymentAccount)
    {
        if ($paymentAccount->is_active && Payment::where('payment_account_id', $paymentAccount->id)->exists()) {
            $paymentAccount->update(['is_active' => false, 'is_default' => false]);

            return back()->with('success', 'تم تعطيل الوعاء — لن يظهر في شاشات الدفع، وحركاته السابقة باقية.');
        }

        $paymentAccount->update(['is_active' => !$paymentAccount->is_active]);

        return back()->with('success', $paymentAccount->is_active ? 'تم تفعيل الوعاء.' : 'تم تعطيل الوعاء.');
    }

    private function validatePayload(Request $request, ?PaymentAccount $existing = null): array
    {
        return $request->validate([
            'name'            => 'required|string|max:120',
            'type'            => 'required|in:' . implode(',', array_keys(PaymentAccount::TYPES)),
            'bank_name'       => 'nullable|string|max:120',
            'account_number'  => 'nullable|string|max:60',
            'iban'            => 'nullable|string|max:60',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'is_default'      => 'nullable|boolean',
            'sort_order'      => 'nullable|integer|min:0|max:999',
            'notes'           => 'nullable|string|max:500',
        ], [
            'name.required' => 'اسم الوعاء مطلوب (مثال: بنك الكريمي — الحساب الجاري)',
            'type.required' => 'نوع الوعاء مطلوب',
            'type.in'       => 'نوع الوعاء غير معروف',
        ]);
    }

    /**
     * الحقول الرقمية الاختيارية تصل من النموذج نصاً فارغاً فتتحوّل null، بينما
     * أعمدتها NOT NULL بقيم افتراضية — فترتدّ من القاعدة خطأً غامضاً («أحد
     * الحقول المطلوبة وصل فارغاً») بعد أن يكون حساب الشجرة قد أُنشئ. تُملأ هنا
     * صراحةً بقيمها الافتراضية بدل أن يُترك الأمر للقاعدة.
     *
     * @param  array<string,mixed>  $validated
     * @return array<string,mixed>
     */
    private function withColumnDefaults(array $validated): array
    {
        $validated['commission_rate'] = $validated['commission_rate'] ?? 0;
        $validated['sort_order']      = $validated['sort_order'] ?? 0;
        $validated['is_default']      = (bool) ($validated['is_default'] ?? false);

        return $validated;
    }

    /** وسيلة افتراضية واحدة لكل نوع — وإلا صار الاختيار التلقائي عشوائياً. */
    private function enforceSingleDefault(PaymentAccount $account): void
    {
        if (!$account->is_default) {
            return;
        }

        PaymentAccount::where('type', $account->type)
            ->where('id', '!=', $account->id)
            ->update(['is_default' => false]);
    }
}
