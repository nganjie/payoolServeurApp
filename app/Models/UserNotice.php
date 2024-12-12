<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserNotice extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    protected $casts = [
        'user_id'   => 'integer',
        'name'   => 'string',
        'designation'   => 'string',
        'rating'   => 'integer',
        'details'   => 'string',
        'image'   => 'string',
    ];
    public function user() {
        return $this->belongsTo(User::class);
    }
    public function scopeAuth($query) {
        $query->where("user_id",auth()->user()->id);
    }
}
