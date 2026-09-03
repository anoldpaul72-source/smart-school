<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Timetable extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'timetables';

    protected $fillable = [
        'school_name',
        'class_name',
        'day_of_week',
        'period_number',
        'time_slot',
        'subject_id',
        'teacher_id',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
