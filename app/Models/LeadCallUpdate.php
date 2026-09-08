<?php
// ================================================================
// FILE: app/Models/LeadCallUpdate.php
// ================================================================
namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadCallUpdate extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'lead_id','user_id','called_at','call_type',
        'duration_minutes','outcome','notes','next_follow_up','followup_time',
        'outcome_subcategory','company_id'
    ];

    protected $casts = [
        'called_at'       => 'datetime',
        'next_follow_up'  => 'date',
    ];

    const CALL_TYPES = [
        'outgoing' => 'Outgoing',
        'incoming' => 'Incoming',
        'missed'   => 'Missed',
    ];

    const OUTCOMES = [
        'interested'     => 'Interested',
        'not_interested' => 'Not Interested',
        'callback'       => 'Callback Requested',
        'no_answer'      => 'No Answer',
        'follow_up'      => 'Follow-up Needed',
        'closed'         => 'Closed / Won',
    ];

    const OUTCOME_COLORS = [
        'interested'     => ['bg'=>'#f0fdf4','text'=>'#16a34a'],
        'not_interested' => ['bg'=>'#fef2f2','text'=>'#dc2626'],
        'callback'       => ['bg'=>'#eff6ff','text'=>'#2563eb'],
        'no_answer'      => ['bg'=>'#f5f4f6','text'=>'#7c7c7c'],
        'follow_up'      => ['bg'=>'#fffbeb','text'=>'#b45309'],
        'closed'         => ['bg'=>'#f0fdf4','text'=>'#059669'],
    ];

    public function lead()   { return $this->belongsTo(Lead::class); }
    public function user()   { return $this->belongsTo(User::class)->withoutGlobalScopes(); }

    public function getOutcomeLabelAttribute(): string
    {
        if ($this->relationLoaded('outCome') && $this->outCome?->name) {
            return $this->outCome->name;
        }
        if (is_numeric($this->outcome)) {
            $cat = OutcomeCategory::find($this->outcome);
            if ($cat) {
                return $cat->name;
            }
        }
        return self::OUTCOMES[$this->outcome] ?? ucfirst((string) $this->outcome);
    }

    public function getOutcomeSubcategoryLabelAttribute(): ?string
    {
        if ($this->relationLoaded('outComeSubCategory') && $this->outComeSubCategory?->name) {
            return $this->outComeSubCategory->name;
        }
        if (is_numeric($this->outcome_subcategory)) {
            $sub = OutcomeSubCategory::find($this->outcome_subcategory);
            if ($sub) {
                return $sub->name;
            }
        }
        return $this->outcome_subcategory ? (string) $this->outcome_subcategory : null;
    }

    public function getOutcomeColorAttribute(): array
    {
        return self::OUTCOME_COLORS[$this->outcome] ?? ['bg'=>'#f5f4f6','text'=>'#7c7c7c'];
    }

    public function getCallTypeLabelAttribute(): string
    {
        return self::CALL_TYPES[$this->call_type] ?? ucfirst((string) $this->call_type);
    }

    public function outCome()
    {
        return $this->belongsTo(OutcomeCategory::class, 'outcome', 'id');
    }

    public function outComeSubCategory()
    {
        return $this->belongsTo(OutcomeSubCategory::class, 'outcome_subcategory', 'id');
    }
}
