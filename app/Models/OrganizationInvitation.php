<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrganizationInvitation extends Model
{
    protected $table = 'organization_invitations';

    protected $fillable = [
        'organization_id',
        'user_id',
        'role',
        'status',
        'responded_at',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
