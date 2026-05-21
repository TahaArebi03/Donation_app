<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = ['title', 'description', 'goal_amount',
     'current_amount', 'status', 'organization_id'];




    public function isActive(){
        return $this->status === 'active';
    }
    public function isCompleted(){
        return $this->status === 'completed';
    }
    public function isCancelled(){
        return $this->status === 'cancelled';
    }
    




    // العلاقة بين المشروع و المنظمة علاقة عديد لواحد
    public function organization(){
        return $this->belongsTo(Organization::class);
    }
    // العلاقة بين المشروع و الصور علاقة عديد لواحد
    public function images(){
        return $this->hasMany(ProjectImage::class);
    }
    // العلاقة بين المشروع و المحفظة علاقة واحد لواحد
    public function wallet(){
        return $this->hasOne(Wallet::class);
    }

    // العلاقة بين المشروع و التبرعات علاقة عديد لواحد
    public function donations(){
        return $this->hasMany(Donation::class);
    }
}
