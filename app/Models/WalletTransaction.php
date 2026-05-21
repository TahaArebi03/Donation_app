<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    protected $fillable = [
        'amount',
        'type',
    ];
    public function wallet(){
        return $this->belongsTo(Wallet::class);
    } 
    public function payment(){
        return $this->hasOne(Payment::class);
    }
}
