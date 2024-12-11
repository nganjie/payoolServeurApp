<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VirtualCardApi extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $casts = [
        'name'=>'string',
        'admin_id' => 'integer',
        'config' => 'object',
        'card_details' => 'string',
        'card_limit' => 'integer',
        'is_active'=>'boolean',
        'is_created_card'=>'boolean',
        'is_rechargeable'=>'boolean',
        'is_withdraw'=>'boolean'
    ];
}
