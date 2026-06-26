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
    use HasFactory, Notifiable, HasApiTokens;

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

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ===== دوال التحقق من الصلاحيات =====
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isOrganization(): bool
    {
        return $this->role === 'organization';
    }

    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    public function canCreateProject()
    {
        // هذه الدالة تحتاج إلى العلاقة organization() (المعرفة أدناه)
        return $this->isOrganization() && optional($this->organization)->status === 'approved';
    }

    public function canDonate()
    {
        return $this->isUser() && optional($this->wallet)->balance > 0;
    }

    // ===== العلاقات =====

    // المنظمة التي يملكها المستخدم (كمدير)
    public function organization()
    {
        return $this->hasOne(Organization::class, 'owner_id');
    }


    // المنظمات التي انضم إليها كعضو (عبر الجدول الوسيط organization_users)
    public function organizations()
    {
        return $this->belongsToMany(Organization::class, 'organization_users')
                    ->withPivot('role', 'joined_at')
                    ->withTimestamps();
    }

    // باقي العلاقات الأخرى (profile, wallet, donations, etc.)
    public function admin()
    {
        return $this->hasOne(Admin::class);
    }

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function donations()
    {
        return $this->hasMany(Donation::class);
    }

    public function recurringDonations()
    {
        return $this->hasMany(RecurringDonation::class);
    }

    // إذا كنت تستخدم جدول 'members' للتطوع فاحتفظ به، وإلا يمكنك حذفه
    // public function volunteerOrganizations()
    // {
    //     return $this->belongsToMany(Organization::class, 'members')->withTimestamps();
    // }
}