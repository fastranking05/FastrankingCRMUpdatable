<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZoomAccount extends Model
{
    use HasFactory;

    protected $table = 'zoom_accounts';

    protected $fillable = [
        'username',
        'account_name',
        'account_id',
        'client_id',
        'client_secret',
        'secret_token',
        'email',
    ];

    protected $hidden = [
        'client_secret',
        'secret_token',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
