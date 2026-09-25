<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    protected $fillable = [
        'school_id',
        'school_class_id',
        'last_name',
        'first_name',
        'birth_date',
        'photo_path',
        'photo_position_x',
        'photo_position_y',
        'status',
        'parent_phone',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function guardians(): HasMany
    {
        return $this->hasMany(StudentGuardian::class);
    }

    public function getAgeAttribute(): ?int
    {
        return $this->birth_date?->age;
    }
}
