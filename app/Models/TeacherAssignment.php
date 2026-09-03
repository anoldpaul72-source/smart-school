<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class TeacherAssignment extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'teacher_assignments';

    protected $fillable = [
        'teacher_id',
        'subject_id',
        'class_name',
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }
}
