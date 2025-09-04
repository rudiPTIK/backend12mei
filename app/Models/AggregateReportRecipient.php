<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class AggregateReportRecipient extends Model
{
    protected $fillable = [
        'report_id',
        'recipient_id',
        'sender_id',       // ⬅️ tambahkan
        'recipient_role',
        'note',
        'sent_at',
        'read_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    public function report()    { return $this->belongsTo(AggregateReport::class, 'report_id'); }
    public function recipient() { return $this->belongsTo(User::class, 'recipient_id'); }
    public function sender()    { return $this->belongsTo(User::class, 'sender_id'); } // ⬅️ tambahkan
}
