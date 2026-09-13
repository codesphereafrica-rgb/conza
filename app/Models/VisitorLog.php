<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitorLog extends Model
{
    protected $table = 'visitors_logs';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = null;

    protected $fillable = [
        'ip',
        'country',
        'city',
        'isp',
        'user_agent',
        'device_model',
        'platform',
        'page_visitee',
        'user_id',
        'is_connected',
        'temps_passe',
        'nombre_tentatives_login',
        'last_seen',
    ];

    protected $casts = [
        'is_connected' => 'boolean',
        'last_seen' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
