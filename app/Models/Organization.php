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

    // الأعضاء المضافين (عبر جدول organization_users)
    public function members()
    {
        return $this->belongsToMany(User::class, 'organization_users')
                    ->withPivot('role', 'joined_at')
                    ->withTimestamps();
    }

    // المشاريع التابعة للمنظمة
    public function projects()
    {
        return $this->hasMany(Project::class);
    }
}