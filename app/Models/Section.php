<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'section_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
    ];

    /**
     * Get the departments that belong to the section.
     */
    public function departments()
    {
        return $this->belongsToMany(Department::class, 'department_sections', 'section_id', 'department_id');
    }
}
