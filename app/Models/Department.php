<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Get the users associated with the department.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the sections associated with the department.
     */
    public function sections()
    {
        return $this->belongsToMany(Section::class, 'department_sections', 'department_id', 'section_id');
    }
}
