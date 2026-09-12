<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SmmSheet extends Model
{
    use HasFactory, BelongsToCompany, SoftDeletes;

    protected $fillable = [
        'company_id',
        'lead_id',
        'lead_product_id',
        'production_initiation_id',
        'product_id',
        'department_id',
        'start_date',
        'end_date',
        'delivery_date',
        'committed_posters',
        'committed_videos',
        'design_completed_posters',
        'design_completed_videos',
        'dm_completed_posters',
        'dm_completed_videos',
        'status',
        'custom_form_data',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'delivery_date' => 'date',
        'committed_posters' => 'integer',
        'committed_videos' => 'integer',
        'design_completed_posters' => 'integer',
        'design_completed_videos' => 'integer',
        'dm_completed_posters' => 'integer',
        'dm_completed_videos' => 'integer',
        'custom_form_data' => 'array',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function leadProduct(): BelongsTo
    {
        return $this->belongsTo(LeadProduct::class);
    }

    public function productionInitiation(): BelongsTo
    {
        return $this->belongsTo(ProductionInitiation::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(SmmSheetLog::class)->latest();
    }

    public function getPendingPostersAttribute(): int
    {
        return max(0, (int) $this->committed_posters - ((int) $this->design_completed_posters + (int) $this->dm_completed_posters));
    }

    public function getPendingVideosAttribute(): int
    {
        return max(0, (int) $this->committed_videos - ((int) $this->design_completed_videos + (int) $this->dm_completed_videos));
    }

    public function getDesignPendingPostersAttribute(): int
    {
        return max(0, (int) $this->committed_posters - (int) $this->design_completed_posters);
    }

    public function getDesignPendingVideosAttribute(): int
    {
        return max(0, (int) $this->committed_videos - (int) $this->design_completed_videos);
    }

    public function getDmPendingPostersAttribute(): int
    {
        return max(0, (int) $this->committed_posters - (int) $this->dm_completed_posters);
    }

    public function getDmPendingVideosAttribute(): int
    {
        return max(0, (int) $this->committed_videos - (int) $this->dm_completed_videos);
    }

    public function recalculateStatus(): string
    {
        $totalCommitted = (int) $this->committed_posters + (int) $this->committed_videos;
        $totalDone = (int) $this->design_completed_posters + (int) $this->design_completed_videos +
                     (int) $this->dm_completed_posters + (int) $this->dm_completed_videos;
        
        $today = now()->toDateString();
        $targetDate = $this->delivery_date?->toDateString() ?: ($this->end_date?->toDateString());

        if ($totalCommitted > 0 && $totalDone >= $totalCommitted) {
            $status = 'completed';
        } elseif ($targetDate && $targetDate < $today) {
            $status = 'overdue';
        } else {
            $status = 'pending';
        }

        $this->status = $status;
        return $status;
    }

    /**
     * Synchronize or create SmmSheet from ProductionInitiation
     */
    public static function syncFromInitiation(ProductionInitiation $initiation): ?self
    {
        // Check if product is count_wise_report and is_this_renewal_product
        $product = $initiation->product ?: Product::find($initiation->product_id);
        if (! $product || ! $product->count_wise_report || ! $product->is_this_renewal_product) {
            return null;
        }

        $formData = is_array($initiation->custom_form_data)
            ? $initiation->custom_form_data
            : (json_decode($initiation->custom_form_data ?? '[]', true) ?? []);

        if (! is_array($formData)) {
            $formData = [];
        }

        $startDate = null;
        $endDate   = null;
        $committedPosters = 0;
        $committedVideos  = 0;

        foreach ($formData as $field) {
            if (! is_array($field)) {
                continue;
            }

            $fieldKey   = $field['field_name'] ?? '';
            $fieldLabel = $field['label'] ?? ($field['key'] ?? '');
            $fieldVal   = $field['value'] ?? '';

            $key   = strtolower(is_array($fieldKey) ? implode(' ', array_filter(array_map('strval', $fieldKey))) : trim((string) $fieldKey));
            $label = strtolower(is_array($fieldLabel) ? implode(' ', array_filter(array_map('strval', $fieldLabel))) : trim((string) $fieldLabel));

            if (is_array($fieldVal)) {
                $value = implode(', ', array_filter(array_map(fn($v) => is_array($v) ? json_encode($v) : (string) $v, $fieldVal)));
            } else {
                $value = trim((string) $fieldVal);
            }

            if (in_array($key, ['start_date', 'startdate', 'start date', 'smm_start_date', 'Start Date', 'ovp_start_date'])) {
                $startDate = $value ?: null;
            }
            if (in_array($key, ['end_date', 'enddate', 'end date', 'smm_end_date', 'End Date', 'ovp_end_date'])) {
                $endDate = $value ?: null;
            }
            if ($label === 'number of posters' || $label === 'number of poster' || str_contains($key, 'number_of_posters') || str_contains($key, 'poster_count')) {
                $committedPosters = (int) $value;
            }
            if ($label === 'number of videos' || $label === 'number of video' || str_contains($key, 'number_of_videos') || str_contains($key, 'video_count')) {
                $committedVideos = (int) $value;
            }
        }

        // Also check production_count_reports if committed is 0
        if ($committedPosters === 0 && $committedVideos === 0) {
            $pcr = \DB::table('production_count_reports')
                ->where('production_initiation_id', $initiation->id)
                ->first();
            if ($pcr) {
                $committedPosters = (int) ($pcr->poster_count ?? 0);
                $committedVideos  = (int) ($pcr->video_count ?? 0);
            }
        }

        $smmSheet = self::firstOrNew([
            'production_initiation_id' => $initiation->id,
        ]);

        $isNew = ! $smmSheet->exists;

        $smmSheet->company_id       = $initiation->company_id;
        $smmSheet->lead_id          = $initiation->lead_id;
        $smmSheet->lead_product_id  = $initiation->lead_product_id;
        $smmSheet->product_id       = $initiation->product_id;
        $smmSheet->department_id    = $initiation->department_id;
        $smmSheet->start_date       = $startDate ?: $smmSheet->start_date;
        $smmSheet->end_date         = $endDate ?: $smmSheet->end_date;
        $smmSheet->delivery_date    = $initiation->project_delivery_date ?: $smmSheet->delivery_date;
        
        // If committed counts were found, update them
        if ($committedPosters > 0 || $isNew) {
            $smmSheet->committed_posters = $committedPosters;
        }
        if ($committedVideos > 0 || $isNew) {
            $smmSheet->committed_videos = $committedVideos;
        }

        $smmSheet->custom_form_data = $formData;
        $smmSheet->recalculateStatus();
        $smmSheet->save();

        if ($isNew) {
            SmmSheetLog::create([
                'smm_sheet_id' => $smmSheet->id,
                'user_id'      => auth()->id() ?? $initiation->initiated_by,
                'department'   => 'general',
                'action'       => 'production_sync',
                'remarks'      => 'Synced from Production Initiation #' . $initiation->id,
            ]);
        }

        return $smmSheet;
    }
}
