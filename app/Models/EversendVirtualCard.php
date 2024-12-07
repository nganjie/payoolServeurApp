<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EversendVirtualCard extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $table = "eversend_virtual_cards";
    protected $casts = [
        'user_id' => 'integer',
        'card_id' => 'string',
        "security_code"=> "string",
        "expiration"=> "string",
        "currency"=> "string",
        "status"=> "string",
        "is_Physical"=> "boolean",
        "title"=> "string",
        "color"=> "string",
        "name"=> "string",
        "amount"=> "double",
        "brand"=> "string",
        "mask"=> "string",
        "number"=> "string",
        "owner_id"=> "string",
        "is_non_subscription"=> "boolean",
        "last_used_on"=> "timestamp",
        "billing_address"=> "json",
        "is_default"=>"boolean"
    ];


    public function user() {
        return $this->belongsTo(User::class);
    }

}
