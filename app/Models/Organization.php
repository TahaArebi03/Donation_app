<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    protected $fillable = [
        'name',
        'description',
        'type',
        'status',
        'document_path',
        'owner_id', // مهم جداً
    ];

    // ===== دوال الحالة =====
    public function isApproved()
    {
        return $this->status === 'approved';
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    // ===== العلاقات =====

    // المالك (المدير الأساسي)
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
    // المستخدمين الذين يتابعون هذه الجمعية (للإحصائيات فقط، لا نعرضها)
    public function followers()
    {
        return $this->belongsToMany(User::class, 'user_organization_follows')
                    ->withPivot('followed_at')
                    ->withTimestamps();
    }

    // للحصول على عدد المتابعين فقط (بدون عرض أسمائهم)
    public function followersCount()
    {
        return $this->followers()->count();
    }
    // الأعضاء المضافين (عبر جدول organization_members)
    public function members()
    {
        return $this->belongsToMany(User::class, 'organization_members')
                    ->withPivot('role', 'joined_at', 'status')
                    ->withTimestamps();
    }

    // المشاريع التابعة للمنظمة
    public function projects()
    {
        return $this->hasMany(Project::class);
    }
}