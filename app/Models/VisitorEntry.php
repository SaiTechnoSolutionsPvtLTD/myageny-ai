<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VisitorEntry extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_CHECKED_IN = 'checked_in';
    public const STATUS_CHECKED_OUT = 'checked_out';

    public const TYPE_CANDIDATE = 'candidate';
    public const TYPE_CLIENT = 'client';
    public const TYPE_OTHERS = 'others';

    public const VISITOR_TYPES = [
        self::TYPE_CANDIDATE => 'Candidate',
        self::TYPE_CLIENT => 'Client',
        self::TYPE_OTHERS => 'Others',
    ];

    protected $fillable = [
        'visitor_name',
        'visitor_type',
        'mobile_number',
        'email',
        'applied_position',
        'company_name',
        'visit_date',
        'in_time',
        'out_time',
        'person_to_meet',
        'status',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'visit_date' => 'date',
        ];
    }

    public static function statusFor(?string $outTime): string
    {
        return $outTime ? self::STATUS_CHECKED_OUT : self::STATUS_CHECKED_IN;
    }

    public function getVisitorTypeLabelAttribute(): string
    {
        return self::VISITOR_TYPES[$this->visitor_type] ?? ucfirst($this->visitor_type ?: 'Others');
    }
}
