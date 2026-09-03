<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class StudentPayment extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'student_payments';

    protected $fillable = [
        'student_id',
        'amount_paid',
        'payment_date',
        'receipt_no',
        'academic_year',
        'recorded_by',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
