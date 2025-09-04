<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class AggregateReport extends Model
{
    protected $fillable = [
        'counselor_id',
        'period_type',
        'period_start',
        'period_end',
        'title',
        'summary',
        'total_sessions',
        'total_students',
        'items',        // gunakan 'items' (json) sesuai migrasi
        'note',
        'published_at',
        'sent_at',      // ⬅️ tambahkan
    ];

    protected $casts = [
        'period_start'   => 'date',
        'period_end'     => 'date',
        'published_at'   => 'datetime',
        'sent_at'        => 'datetime', // ⬅️ tambahkan
        'items'          => 'array',
        'total_sessions' => 'integer',
        'total_students' => 'integer',
    ];

    public function counselor() { return $this->belongsTo(User::class, 'counselor_id'); }
    public function recipients() { return $this->hasMany(AggregateReportRecipient::class, 'report_id'); }
}
