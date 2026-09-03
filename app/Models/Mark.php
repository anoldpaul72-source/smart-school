<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Mark extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'marks';

    protected $fillable = [
        'student_id',
        'subject_id',
        'score',
        'term',
        'year',
        'exam_date',
        'recorded_by',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function getGradeAttribute(): string
    {
        $score = (float) $this->score;
        if ($score >= 75) return 'A';
        if ($score >= 65) return 'B';
        if ($score >= 45) return 'C';
        if ($score >= 30) return 'D';
        return 'F';
    }

    public function getRemarksAttribute(): string
    {
        $score = (float) $this->score;
        if ($score >= 75) return 'Bora Sana (Excellent)';
        if ($score >= 65) return 'Vizuri Sana (Very Good)';
        if ($score >= 45) return 'Vizuri (Good)';
        if ($score >= 30) return 'Inaridhisha (Satisfactory)';
        return 'Inahitaji Juhudi (Needs Improvement)';
    }
}
