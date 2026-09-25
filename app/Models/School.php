<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    protected $fillable = [
        'country',
        'school_type',
        'name',
        'slogan',
        'logo_path',
        'representative_photo_path',
        'director_name',
        'primary_color',
        'secondary_color',
        'exit_hours',
        'late_penalty_note',
        'address',
        'phone',
        'email',
    ];

    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }
}
