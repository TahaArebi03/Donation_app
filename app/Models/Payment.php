<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    public function donation(){
        return $this->belongsTo(Donation::class);
    }
    public function transaction(){
        return $this->belongsTo(WalletTransaction::class);
    }
}
