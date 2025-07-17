<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'client_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'phone',
        'email',
        'billing_address',
    ];

    /**
     * Get the orders for the client.
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'client_id');
    }
}
