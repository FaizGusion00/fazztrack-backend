<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileAttachment extends Model
{
    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'file_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'file_path',
        'file_name',
    ];

    /**
     * Get the payments that use this file as receipt.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class, 'receipt_file_id');
    }

    /**
     * Get the order designs that use this file.
     */
    public function orderDesigns()
    {
        return $this->hasMany(OrderDesign::class, 'design_file_id');
    }
}
