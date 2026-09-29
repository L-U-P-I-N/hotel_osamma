<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalLine extends Model
{
    protected $fillable = ['journal_entry_id', 'account_code', 'debit', 'credit', 'notes'];

    protected $casts = [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
    ];

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /** الحساب في شجرة USALI — الربط بالكود لا بالمعرّف (كود الحساب محاسبيّ ثابت). */
    public function account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_code', 'code');
    }
}
