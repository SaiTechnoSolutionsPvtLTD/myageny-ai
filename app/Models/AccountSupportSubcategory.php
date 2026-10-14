<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountSupportSubcategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'account_support_subcategories';

    protected $fillable = [
        'company_id',
        'account_support_category_id',
        'name',
        'code',
        'description',
        'status',
        'hierarchy',
        'created_by',
    ];

    protected $casts = [
        'hierarchy' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(AccountSupportCategory::class, 'account_support_category_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Get all unique user IDs involved across all hierarchy levels.
     */
    public function getAllHierarchyUserIds(): array
    {
        $levels = $this->hierarchy;
        if (!is_array($levels)) {
            return [];
        }

        $userIds = [];
        foreach ($levels as $lvl) {
            if (!empty($lvl['user_ids']) && is_array($lvl['user_ids'])) {
                foreach ($lvl['user_ids'] as $uid) {
                    if ($uid) $userIds[] = (int) $uid;
                }
            } elseif (!empty($lvl['user_id'])) {
                $userIds[] = (int) $lvl['user_id'];
            }
        }

        return array_values(array_unique($userIds));
    }

    /**
     * Get detailed hierarchy list with loaded User models.
     */
    public function getFormattedHierarchyAttribute(): array
    {
        $levels = $this->hierarchy;
        if (!is_array($levels) || empty($levels)) {
            return [];
        }

        $allUserIds = $this->getAllHierarchyUserIds();
        $usersMap = empty($allUserIds) ? collect() : User::whereIn('id', $allUserIds)->get()->keyBy('id');

        $result = [];
        foreach ($levels as $idx => $lvl) {
            $levelNumber = $lvl['level'] ?? ($idx + 1);
            $levelName   = $lvl['level_name'] ?? "Level {$levelNumber}";
            $notes       = $lvl['notes'] ?? '';

            $uids = [];
            if (!empty($lvl['user_ids']) && is_array($lvl['user_ids'])) {
                $uids = array_map('intval', array_filter($lvl['user_ids']));
            } elseif (!empty($lvl['user_id'])) {
                $uids = [(int) $lvl['user_id']];
            }

            $levelUsers = [];
            foreach ($uids as $uid) {
                if ($user = $usersMap->get($uid)) {
                    $levelUsers[] = [
                        'id'          => $user->id,
                        'name'        => $user->name,
                        'email'       => $user->email,
                        'designation' => $user->designation,
                    ];
                }
            }

            $result[] = [
                'level'      => $levelNumber,
                'level_name' => $levelName,
                'notes'      => $notes,
                'user_ids'   => $uids,
                'users'      => $levelUsers,
            ];
        }

        return $result;
    }
}
