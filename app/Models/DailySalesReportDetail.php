<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'daily_work_report_id',
    'leads_received_count',
    'leads_contacted_count',
    'calls_made_count',
    'messages_sent_count',
    'followups_done_count',
    'meetings_booked_count',
    'proposals_sent_count',
    'deals_closed_count',
    'revenue_generated',
    'pending_leads_count',
    'lost_leads_count',
    'lost_lead_reasons',
    'next_followup_targets',
])]
class DailySalesReportDetail extends Model
{
    use HasFactory;

    protected $attributes = [
        'leads_received_count' => 0,
        'leads_contacted_count' => 0,
        'calls_made_count' => 0,
        'messages_sent_count' => 0,
        'followups_done_count' => 0,
        'meetings_booked_count' => 0,
        'proposals_sent_count' => 0,
        'deals_closed_count' => 0,
        'revenue_generated' => 0,
        'pending_leads_count' => 0,
        'lost_leads_count' => 0,
    ];

    public function dailyWorkReport(): BelongsTo
    {
        return $this->belongsTo(DailyWorkReport::class);
    }

    protected function casts(): array
    {
        return [
            'leads_received_count' => 'integer',
            'leads_contacted_count' => 'integer',
            'calls_made_count' => 'integer',
            'messages_sent_count' => 'integer',
            'followups_done_count' => 'integer',
            'meetings_booked_count' => 'integer',
            'proposals_sent_count' => 'integer',
            'deals_closed_count' => 'integer',
            'revenue_generated' => 'decimal:4',
            'pending_leads_count' => 'integer',
            'lost_leads_count' => 'integer',
        ];
    }
}
