<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    protected $fillable = [
        'name',
        'code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the Qupa Admin users assigned to this branch
     */
    public function qupaAdmins(): HasMany
    {
        return $this->hasMany(User::class, 'branch_id')
            ->where('role', User::ROLE_QUPA_ADMIN);
    }

    /**
     * Get applications assigned to this branch
     */
    public function applications(): HasMany
    {
        return $this->hasMany(ApplicationState::class, 'assigned_branch_id');
    }

    /**
     * Scope for active branches
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
