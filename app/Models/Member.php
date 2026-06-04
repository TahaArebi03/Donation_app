<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    protected $fillable = ['organization_id', 'user_id', 'role_in_org'];

     // العلاقة بين العضو و المنظمة علاقة عديد لعديد
     
    public function organization(){
        return $this->belongsToMany(Organization::class, 'members')->withTimestamps();
    }
    // العلاقة بين العضو و اليوزر علاقة عديد لعديد
    public function user(){
        return $this->belongsToMany(User::class, 'members')->withTimestamps();
    }
}
