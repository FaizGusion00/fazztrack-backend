<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'payment_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'type',
        'payment_method',
        'amount',
        'payment_date',
        'remarks',
        'receipt_file_id',
        'status',
        'approved_by',
        'approved_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'approved_at' => 'datetime',
    ];

    /**
     * Get the order that owns the payment.
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Get the receipt file attachment.
     */
    public function receiptFile()
    {
        return $this->belongsTo(FileAttachment::class, 'receipt_file_id');
    }

    /**
     * Get the user who approved the payment.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
