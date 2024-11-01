<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiApp extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $casts = [
        'name'=>'string',
        'status' => 'bool',
    ];
}
