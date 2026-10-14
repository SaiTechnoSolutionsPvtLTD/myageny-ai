<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountExpense extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'account_expenses';

    protected $fillable = [
        'company_id',
        'expense_number',
        'expense_date',
        'account_expense_category_id',
        'account_expense_subcategory_id',
        'vendor_id',
        'amount',
        'expense_type',
        'is_recurring',
        'recurring_day_of_month',
        'recurring_end_date',
        'auto_generated',
        'parent_expense_id',
        'last_generated_month',
        'invoice_no',
        'remarks',
        'attachments',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'amount'                  => 'decimal:2',
        'expense_date'            => 'date',
        'attachments'             => 'array',
        'is_recurring'            => 'boolean',
        'auto_generated'          => 'boolean',
        'recurring_day_of_month'  => 'integer',
        'recurring_end_date'      => 'date',
    ];

    protected static function booted()
    {
        static::creating(function ($expense) {
            if (empty($expense->expense_number)) {
                $prefix = 'EXP-' . now()->format('Ym') . '-';
                $latest = static::where('expense_number', 'like', $prefix . '%')
                    ->latest('id')
                    ->value('expense_number');

                $nextNumber = 1;
                if ($latest && preg_match('/-(\d+)$/', $latest, $matches)) {
                    $nextNumber = intval($matches[1]) + 1;
                }

                $expense->expense_number = $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function category()
    {
        return $this->belongsTo(AccountExpenseCategory::class, 'account_expense_category_id');
    }

    public function subcategory()
    {
        return $this->belongsTo(AccountExpenseSubcategory::class, 'account_expense_subcategory_id');
    }

    public function vendor()
    {
        return $this->belongsTo(VendorMaster::class, 'vendor_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Get all attachments formatted with url, name, size, is_image, is_pdf.
     */
    public function getAttachmentListAttribute(): array
    {
        $list = [];

        if (! empty($this->attachments) && is_array($this->attachments)) {
            foreach ($this->attachments as $index => $item) {
                if (is_array($item) && ! empty($item['path'])) {
                    $path = ltrim((string) $item['path'], '/');
                    $fullPath = public_path($path);
                    $exists = file_exists($fullPath);
                    $size = $item['size'] ?? ($exists ? @filesize($fullPath) : null);
                    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

                    $list[] = [
                        'index'          => $index,
                        'path'           => $path,
                        'name'           => $item['name'] ?? basename($path),
                        'size'           => $size,
                        'formatted_size' => $size ? $this->formatFileSize($size) : null,
                        'mime_type'      => $item['mime_type'] ?? null,
                        'extension'      => $ext,
                        'is_image'       => in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg']),
                        'is_pdf'         => $ext === 'pdf',
                        'url'            => asset($path),
                    ];
                } elseif (is_string($item) && ! empty($item)) {
                    $path = ltrim($item, '/');
                    $fullPath = public_path($path);
                    $exists = file_exists($fullPath);
                    $size = $exists ? @filesize($fullPath) : null;
                    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

                    $list[] = [
                        'index'          => $index,
                        'path'           => $path,
                        'name'           => basename($path),
                        'size'           => $size,
                        'formatted_size' => $size ? $this->formatFileSize($size) : null,
                        'mime_type'      => null,
                        'extension'      => $ext,
                        'is_image'       => in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg']),
                        'is_pdf'         => $ext === 'pdf',
                        'url'            => asset($path),
                    ];
                }
            }
        }

        return $list;
    }

    protected function formatFileSize($bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }

    public function scopeForCompany($query, $companyId)
    {
        if ($companyId) {
            return $query->where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId)->orWhereNull('company_id');
            });
        }
        return $query;
    }

    public function scopeFixed($query)
    {
        return $query->where('expense_type', 'Fixed');
    }

    public function scopeVariable($query)
    {
        return $query->where('expense_type', 'Variable');
    }

    public function parent()
    {
        return $this->belongsTo(AccountExpense::class, 'parent_expense_id');
    }

    public function generatedChildren()
    {
        return $this->hasMany(AccountExpense::class, 'parent_expense_id');
    }

    /**
     * Active recurring master expenses (templates that generate monthly entries)
     */
    public function scopeActiveRecurring($query, $forDate = null)
    {
        $targetDate = $forDate ? \Carbon\Carbon::parse($forDate) : now();

        return $query->where('expense_type', 'Fixed')
            ->where('is_recurring', true)
            ->whereNull('parent_expense_id')
            ->where(function ($q) use ($targetDate) {
                $q->whereNull('recurring_end_date')
                  ->orWhereDate('recurring_end_date', '>=', $targetDate->toDateString());
            });
    }
}
