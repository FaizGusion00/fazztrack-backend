<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderDesign extends Model
{
    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'design_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'status',
        'designer_id',
        'design_file_id',
    ];

    /**
     * Get the order that owns the design.
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Get the designer that created the design.
     */
    public function designer()
    {
        return $this->belongsTo(User::class, 'designer_id');
    }

    /**
     * Get the file attachment for the design.
     */
    public function designFile()
    {
        return $this->belongsTo(FileAttachment::class, 'design_file_id');
    }
}
