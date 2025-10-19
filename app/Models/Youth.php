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
        'group',
        'color'
    ];
}
