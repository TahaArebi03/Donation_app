<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrganizationJoinRequest extends Model
{
    protected $table = 'organization_join_requests';

    protected $fillable = [
        'organization_id',
        'user_id',
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
