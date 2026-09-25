<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentGuardian extends Model
{
    protected $fillable = [
        'student_id',
        'last_name',
        'first_name',
        'photo_path',
        'photo_position_x',
        'photo_position_y',
        'phone',
        'relation',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
