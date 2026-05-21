<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    function organization(){
        return $this->belongsTo(Organization::class);
    }
}
