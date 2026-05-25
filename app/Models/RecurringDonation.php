<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecurringDonation extends Model
{
    //
    protected $fillable = ['user_id', 'project_id', 'amount', 'frequency','next_donation_date', 'status'];
    // العلاقة بين التبرع المتكرر و اليوزر علاقة عديد لواحد
    public function user(){
        return $this->belongsTo(User::class);
    }
    // العلاقة بين التبرع المتكرر و المشروع علاقة عديد لواحد
    public function project(){
        return $this->belongsTo(Project::class);
    }
}
