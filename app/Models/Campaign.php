<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Campaign extends Model
{
    protected $fillable = [
        'name',
        'description',
        'start_date',
        'end_date',
        'status',
        'target_applications',
        'target_sales',
        'target_conversions',
        'performance_metrics',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'performance_metrics' => 'array',
        'target_applications' => 'decimal:2',
        'target_sales' => 'decimal:2',
    ];

    /**
     * The agents assigned to this campaign
     */
    public function agents(): BelongsToMany
    {
        return $this->belongsToMany(Agent::class, 'agent_campaign')
            ->withPivot([
                'applications_count',
                'sales_total',
                'conversions_count',
                'individual_metrics'
            ])
            ->withTimestamps();
    }

    /**
     * Get total applications across all agents
     */
    public function getTotalApplicationsAttribute()
    {
        return $this->agents()->sum('agent_campaign.applications_count');
    }

    /**
     * Get total sales across all agents
     */
    public function getTotalSalesAttribute()
    {
        return $this->agents()->sum('agent_campaign.sales_total');
    }

    /**
     * Get total conversions across all agents
     */
    public function getTotalConversionsAttribute()
    {
        return $this->agents()->sum('agent_campaign.conversions_count');
    }
}
