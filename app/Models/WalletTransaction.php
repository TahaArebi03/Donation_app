<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    protected $fillable = [
        'amount',
        'type',
        'status',
        'user_id',
        'project_id',
        'wallet_id',

    ];
    public function wallet(){
        return $this->belongsTo(Wallet::class);
    } 
    public function payment(){
        return $this->hasOne(Payment::class);
    }
    // public function user(){
    //     return $this->belongsTo(User::class);
    // }


}
