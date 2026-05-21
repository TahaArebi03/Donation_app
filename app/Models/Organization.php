<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    protected $fillable = ['name', 'description', 'type', 'status'];


    public function isApproved(){
        return $this->status === 'approved';
    }

    public function isPending(){
        return $this->status === 'pending';
    }
    public function isRejected(){
        return $this->status === 'rejected';
    }   

    



        // العلاقة بين اليوزر و المنظمة علاقة واحد لواحد
    public function user(){
        return $this->belongsTo(User::class);
    }
     // العلاقة بين المنظمة و المشاريع علاقة عديد لواحد 
    public function projects(){
        return $this->hasMany(Project::class);
    }
        // العلاقة بين المنظمة و الأعضاء علاقة عديد لواحد
    public function members(){
        return $this->hasMany(Profile::class);
    }
}
