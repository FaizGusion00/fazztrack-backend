<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($order) {
            // Generate a unique tracking ID if not set
            if (empty($order->tracking_id)) {
                $order->tracking_id = strtoupper(substr(md5(uniqid(rand(), true)), 0, 10));
            }
        });
    }

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'order_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'client_id',
        'created_by',
        'job_name',
        'status',
        'tracking_id',
        'shipping_address',
        'delivery_method',
        'delivery_tracking_id',
        'due_date_design',
        'due_date_production',
        'estimated_delivery_date',
        'link_download',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'due_date_design' => 'date',
        'due_date_production' => 'date',
        'estimated_delivery_date' => 'date',
    ];

    /**
     * Get the client that owns the order.
     */
    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    /**
     * Get the user that created the order.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the order items for the order.
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    /**
     * Get the payments for the order.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class, 'order_id');
    }

    /**
     * Get the design for the order.
     */
    public function design()
    {
        return $this->hasOne(OrderDesign::class, 'order_id');
    }

    /**
     * Get the jobs for the order.
     */
    public function jobs()
    {
        return $this->hasMany(Job::class, 'order_id');
    }
}
