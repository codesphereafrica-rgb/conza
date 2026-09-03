<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

/**
 * Model DonationArchive
 */

class DonationArchive extends Model
{
    protected $table = 'donations_archive';
    protected $fillable = [
        'original_id', 'user_id', 'amount', 'provider', 'status', 'external_reference', 'donated_at'
    ];

    protected $casts = [
        'donated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
