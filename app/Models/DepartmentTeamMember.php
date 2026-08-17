<?php

namespace App\Models;

use App\Enums\TeamMemberRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'department_team_id',
    'employment_id',
    'role_in_team',
    'joined_at',
    'is_active',
])]
class DepartmentTeamMember extends Model
{
    use HasFactory;

    protected $attributes = [
        'role_in_team' => TeamMemberRole::Member->value,
        'is_active' => true,
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(DepartmentTeam::class, 'department_team_id');
    }

    public function employment(): BelongsTo
    {
        return $this->belongsTo(Employment::class);
    }

    protected function casts(): array
    {
        return [
            'role_in_team' => TeamMemberRole::class,
            'joined_at' => 'date',
            'is_active' => 'boolean',
        ];
    }
}
