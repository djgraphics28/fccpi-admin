<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Youth extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'gender',
        'church',
        'color'
    ];

    public function getFirstNameAttribute($value)
    {
        return strtoupper($value);
    }

    public function getLastNameAttribute($value)
    {
        return strtoupper($value);
    }
}
