<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\RiasecCareer;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class RiasecResult extends Model
{
    protected $fillable = [
        'user_id',
        'score_R', 'score_I', 'score_A',
        'score_S', 'score_E', 'score_C',
        'top_domains'
    ];

    protected $dates = ['created_at'];

    /**
     * Relasi ke User (siswa)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * (Opsional) Relasi ke karir rekomendasi
     */
    public function careers(): BelongsToMany
    {
        return $this->belongsToMany(
            RiasecCareer::class,
            'riasec_career_result', // nama pivot table
            'result_id',            // FK ke RiasecResult
            'career_id'             // FK ke RiasecCareer
        );
    }
}
