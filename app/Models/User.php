<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable,HasApiTokens;

    
    protected $fillable = [
        'firstName',
        'lastName',
        'email',
        'password',
        'role',
    ];
    protected $attributes = [
        'role' => 'user',
    ];
    // public function makeAdmin(){
    //     $this->update([
    //         'role'=>'admin'
    //     ]);
    // }
    public function isAdmin(): bool{
        return $this->role === 'admin';
    }
    public function isOrganization(): bool{
        return $this->role === 'organization';
    }
    public function isUser(): bool{
        return $this->role === 'user';
    }
    public function canCreateProject(){
        return $this->isOrganization()&& optional($this->organization)->status === 'approved';
    }
    public function canDonate(){
        return $this->isUser() && optional($this->wallet)->balance > 0;
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    // العلاقة بين اليوزر و المنظمة علاقة واحد لواحد
    public function organization(){
        return $this->hasOne(Organization::class);
    }
    // العلاقة بين اليوزر و الادمن علاقة واحد لواحد
    public function admin(){
        return $this->hasOne(Admin::class);
    }

    // العلاقة بين اليوزر و البروفايل علاقة واحد لواحد
    public function profile(){
        return $this->hasOne(Profile::class);
    }
    // العلاقة بين اليوزر و المحفظة علاقة واحد لواحد
    public function wallet(){
        return $this->hasOne(Wallet::class);
    }
    // العلاقة بين اليوزر و التبرعات علاقة واحد لعديد
    public function donations(){
        return $this->hasMany(Donation::class);
    }
    // العلاقة بين اليوزر و التبرعات المتكررة علاقة واحد لعديد
    public function recurringDonations(){
        return $this->hasMany(RecurringDonation::class);
    }
    // العلاقة بين اليوزر و المنظمات التطوعية علاقة عديد لعديد
    public function volunteerOrganizations(){
        // members اسم الجدول 
        return $this->belongsToMany(Organization::class,'members')->withTimestamps();
    }
    // العلاقة بين اليوزر و معاملات المحفظة علاقة واحد لعديد
    // public function walletTransactions(){
    //     return $this->hasMany(WalletTransaction::class);
    // }
 
}
