<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MapleradVirtualCard extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $table = "maplerad_virtual_cards";
    protected $casts = [
        'user_id' => 'integer',
        'card_id' => 'string',
        "name"=> "string",
        "card_number"=> "string",
        "masked_pan"=> "string",
        "expiry"=> "string",
        "cvv"=> "string",
        "status"=> "string",
        "type"=> "string",
        "issuer"=> "string",
        "currency"=> "string",
        "balance"=> "double",
        "auto_approve"=> "boolean",
        "address"=> "json",
        "is_default"=>"boolean",
        'is_deleted' => 'boolean',
        'is_penalize' => 'boolean',
        'nb_trx_failed'=>'integer',
    ];


    public function user() {
        return $this->belongsTo(User::class);
    }

}
