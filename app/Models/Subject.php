<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Subject extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'subjects';

    protected $fillable = [
        'subject_name',
        'code',
    ];
}
