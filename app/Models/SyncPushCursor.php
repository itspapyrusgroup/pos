<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncPushCursor extends Model
{
    protected $table = 'sync_push_cursor';

    protected $fillable = [
        'target',
        'dataset',
        'last_updated_at',
        'last_pk',
        'last_sent_rows',
        'last_success_at',
        'last_error',
    ];

    protected $casts = [
        'last_updated_at' => 'datetime',
        'last_success_at' => 'datetime',
    ];
}
