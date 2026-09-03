<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Attendance extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'attendance';

    protected $fillable = [
        'student_id',
        'class_name',
        'status',
        'attendance_date',
        'marked_by',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
