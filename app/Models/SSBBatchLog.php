<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SSBBatchLog extends Model
{
    protected $table = 'ssb_batch_logs';

    protected $fillable = [
        'batch_reference',
        'batch_type',
        'status',
        'file_path',
        'total_records',
        'success_count',
        'failed_count',
        'errors',
        'metadata',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'errors' => 'array',
        'metadata' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
}
