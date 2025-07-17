<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Job extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'jobs';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'job_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'phase',
        'status',
        'assigned_to',
        'start_time',
        'end_time',
        'duration',
        'qr_code_hash',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($job) {
            // Generate a unique hash for QR code if not already set
            if (empty($job->qr_code_hash)) {
                $job->qr_code_hash = Str::random(32);
            }
        });
    }

    /**
     * Get the QR code URL for the job.
     *
     * @return string
     */
    public function getQrCodeUrl()
    {
        return url("/api/jobs/qr/{$this->qr_code_hash}");
    }

    /**
     * Get the order that owns the job.
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Get the user that is assigned to the job.
     */
    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
