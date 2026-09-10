<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registration-status record for the Face Attendance module. The actual
 * biometric encoding lives in the separate face_recognition_myagenci_python
 * service (keyed by employee_id) — this table never stores biometric data,
 * only who/when an employee's face was registered, so the app can:
 *   - show HR/Admin who has/hasn't registered (Face Registration screen),
 *   - hard-block a Face Attendance check-in/out for anyone with no row here,
 *     without needing a round trip to the Python service first.
 */
class EmployeeFaceProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'registered_by',
        'registered_at',
    ];

    protected $casts = [
        'employee_id' => 'integer',
        'registered_by' => 'integer',
        'registered_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeOnboarding::class, 'employee_id');
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }
}
