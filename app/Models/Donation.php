<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Donation extends Model
{
    protected $fillable=[
        'project_id',
        'user_id',
        'amount',
        'status',
    ];
    // العلاقة بين التبرع و اليوزر علاقة عديد لواحد
    public function user(){
        return $this->belongsTo(User::class);
    }
    public function payment(){
        return $this->hasOne(Payment::class);
    }
    // // العلاقة بين التبرع و المشروع علاقة عديد لواحد
    public function project(){
        return $this->belongsTo(Project::class);
    }
}
