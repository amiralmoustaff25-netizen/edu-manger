<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvaluationType extends Model
{
    protected $fillable = ['name', 'code', 'default_coefficient', 'default_scale', 'is_active', 'position'];

    protected $casts = ['default_coefficient' => 'decimal:2', 'is_active' => 'boolean'];
}
