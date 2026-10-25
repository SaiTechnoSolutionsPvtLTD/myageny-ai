<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\AccountExpense;
use App\Models\AccountExpenseCategory;
use App\Models\AccountExpenseSubcategory;
use App\Models\AccountSupportCategory;
use App\Models\AccountSupportSubcategory;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SupportTicketApiController extends Controller
{
    /**
     * Check if a user is Company Admin or Super Admin.
     */
    private function checkIsCompanyAdmin(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->isCompanyAdmin()
            || $user->hasRole('Company Admin')
            || $user->roles->contains(function ($role) {
                $name = strtolower($role->name);
                $display = strtolower($role->display_name ?? '');
                return str_contains($name, 'company_admin')
                    || str_contains($display, 'company admin')
                    || str_contains($name, 'super_admin')
                    || str_contains($display, 'super admin');
            });
    }

    /**
     * Resolve company ID for the user.
     */
    private function resolveCompanyId(User $user): ?int
    {
        $companyId = $user->company_id;
        if (!$companyId) {
            $companyId = \App\Models\EmployeeOnboarding::withoutGlobalScopes()
                ->where(function ($q) use ($user) {
                    $q->where('portal_user_id', $user->id)
                      ->orWhere('email', $user->email);
                })
                ->whereNotNull('company_id')
                ->value('company_id');

            if ($companyId) {
                $user->update(['company_id' => $companyId]);
            }
        }
        return $companyId;
    }

    /**
     * Transform a single SupportTicket model into an array with complete URLs.
     */
    private function transformTicket(SupportTicket $ticket, User $currentUser, bool $isCompanyAdmin): array
    {
        $attachmentsList = [];
        if (!empty($ticket->attachments) && is_array($ticket->attachments)) {
            foreach ($ticket->attachments as $att) {
                if (is_string($att) && !empty($att)) {
                    $url = (str_starts_with($att, 'http://') || str_starts_with($att, 'https://'))
                        ? $att
                        : (str_starts_with($att, 'uploads/') ? asset($att) : asset('storage/' . $att));
                    $attachmentsList[] = [
                        'path' => $att,
                        'name' => basename($att),
                        'url'  => $url,
                    ];
                }
            }
        } elseif (!empty($ticket->attachment_path)) {
            $att = $ticket->attachment_path;
            $url = (str_starts_with($att, 'http://') || str_starts_with($att, 'https://'))
                ? $att
                : (str_starts_with($att, 'uploads/') ? asset($att) : asset('storage/' . $att));
            $attachmentsList[] = [
                'path' => $att,
                'name' => basename($att),
                'url'  => $url,
            ];
        }

        $canUpdateStatus = ($ticket->to_user_id === $currentUser->id || $isCompanyAdmin) && $ticket->status !== 'closed';

        return [
            'id'                             => $ticket->id,
            'company_id'                     => $ticket->company_id,
            'account_support_category_id'    => $ticket->account_support_category_id,
            'account_support_subcategory_id' => $ticket->account_support_subcategory_id,
            'subject'                        => $ticket->subject,
            'message'                        => $ticket->message,
            'status'                         => $ticket->status,
            'remark'                         => $ticket->remark,
            'payment_amount'                 => $ticket->payment_amount !== null ? (float) $ticket->payment_amount : null,
            'attachment_path'                => $ticket->attachment_path,
            'attachment_url'                 => $ticket->attachment_url,
            'attachments'                    => $attachmentsList,
            'resolution_attachment_path'     => $ticket->resolution_attachment_path,
            'resolution_attachment_url'      => $ticket->resolution_attachment_url,
            'created_by'                     => $ticket->created_by,
            'to_user_id'                     => $ticket->to_user_id,
            'created_at'                     => $ticket->created_at?->toIso8601String(),
            'created_at_formatted'           => $ticket->created_at?->format('d M Y, h:i A'),
            'updated_at'                     => $ticket->updated_at?->toIso8601String(),
            'can_update_status'              => $canUpdateStatus,
            'category'                       => $ticket->category ? [
                'id'          => $ticket->category->id,
                'name'        => $ticket->category->name,
                'code'        => $ticket->category->code,
                'description' => $ticket->category->description,
            ] : null,
            'subcategory'                    => $ticket->subcategory ? [
                'id'             => $ticket->subcategory->id,
                'name'           => $ticket->subcategory->name,
                'code'           => $ticket->subcategory->code,
                'description'    => $ticket->subcategory->description,
                'is_expense_add' => (bool) $ticket->subcategory->is_expense_add,
                'hierarchy'      => $ticket->subcategory->hierarchy,
            ] : null,
            'creator'                        => $ticket->creator ? [
                'id'         => $ticket->creator->id,
                'name'       => $ticket->creator->name,
                'email'      => $ticket->creator->email,
                'avatar_url' => $ticket->creator->avatar_url ?? null,
            ] : null,
            'assigned_to'                    => $ticket->assignedTo ? [
                'id'         => $ticket->assignedTo->id,
                'name'       => $ticket->assignedTo->name,
                'email'      => $ticket->assignedTo->email,
                'avatar_url' => $ticket->assignedTo->avatar_url ?? null,
            ] : null,
        ];
    }

    /**
     * GET /api/mobile/support-tickets
     * List support tickets with KPI counts, filters, tabs, and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $userId = $user->id;
        $companyId = $this->resolveCompanyId($user);
        $isCompanyAdmin = $this->checkIsCompanyAdmin($user);

        $selectedCategoryId = $request->input('category_id');
        $selectedSubcategoryId = $request->input('subcategory_id');
        $search = trim((string) $request->input('search'));
        $tab = $request->input('tab', $isCompanyAdmin ? 'all' : 'received');
        $perPage = max(1, min(50, (int) $request->input('per_page', 15)));

        // Filter callback
        $applyFilters = function ($query) use ($selectedCategoryId, $selectedSubcategoryId, $search) {
            if ($selectedCategoryId) {
                $query->where('account_support_category_id', $selectedCategoryId);
            }
            if ($selectedSubcategoryId) {
                $query->where('account_support_subcategory_id', $selectedSubcategoryId);
            }
            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('subject', 'like', "%{$search}%")
                      ->orWhere('message', 'like', "%{$search}%")
                      ->orWhere('remark', 'like', "%{$search}%")
                      ->orWhere('id', 'like', "%{$search}%")
                      ->orWhereHas('creator', fn($cq) => $cq->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                      ->orWhereHas('assignedTo', fn($aq) => $aq->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
                });
            }
        };

        // KPI statistics calculation (matches web SupportController::index)
        if ($isCompanyAdmin) {
            $kpiQuery = SupportTicket::query();
            if ($companyId) {
                $kpiQuery->where(function ($q) use ($companyId) {
                    $q->where('company_id', $companyId)
                      ->orWhereHas('creator', fn($cq) => $cq->where('company_id', $companyId))
                      ->orWhereHas('assignedTo', fn($aq) => $aq->where('company_id', $companyId));
                });
            }
        } else {
            $kpiQuery = SupportTicket::where(function ($q) use ($userId) {
                $q->where('to_user_id', $userId)
                  ->orWhere('created_by', $userId);
            });
        }

        $applyFilters($kpiQuery);

        $statTotal     = (clone $kpiQuery)->count();
        $statPending   = (clone $kpiQuery)->where('status', 'pending')->count();
        $statOnProcess = (clone $kpiQuery)->where('status', 'onprocess')->count();
        $statResolved  = (clone $kpiQuery)->whereIn('status', ['resolved', 'closed'])->count();

        // Calculate Tab Counts
        $allCount = 0;
        if ($isCompanyAdmin) {
            $allBaseQuery = SupportTicket::query();
            if ($companyId) {
                $allBaseQuery->where(function ($q) use ($companyId) {
                    $q->where('company_id', $companyId)
                      ->orWhereHas('creator', fn($cq) => $cq->where('company_id', $companyId))
                      ->orWhereHas('assignedTo', fn($aq) => $aq->where('company_id', $companyId));
                });
            }
            $applyFilters($allBaseQuery);
            $allCount = $allBaseQuery->count();
        }

        $receivedBaseQuery = SupportTicket::where('to_user_id', $userId);
        $applyFilters($receivedBaseQuery);
        $receivedCount = $receivedBaseQuery->count();

        $createdBaseQuery = SupportTicket::where('created_by', $userId);
        $applyFilters($createdBaseQuery);
        $createdCount = $createdBaseQuery->count();

        // Active List Query based on tab
        $query = SupportTicket::with(['creator', 'assignedTo', 'category', 'subcategory']);

        if ($tab === 'all') {
            if ($isCompanyAdmin) {
                if ($companyId) {
                    $query->where(function ($q) use ($companyId) {
                        $q->where('company_id', $companyId)
                          ->orWhereHas('creator', fn($cq) => $cq->where('company_id', $companyId))
                          ->orWhereHas('assignedTo', fn($aq) => $aq->where('company_id', $companyId));
                    });
                }
            } else {
                $query->where(function ($q) use ($userId) {
                    $q->where('to_user_id', $userId)
                      ->orWhere('created_by', $userId);
                });
            }
        } elseif ($tab === 'created') {
            $query->where('created_by', $userId);
        } else {
            // default 'received'
            $tab = 'received';
            $query->where('to_user_id', $userId);
        }

        $applyFilters($query);

        $paginator = $query->latest('id')->paginate($perPage);

        $tickets = collect($paginator->items())->map(function ($ticket) use ($user, $isCompanyAdmin) {
            return $this->transformTicket($ticket, $user, $isCompanyAdmin);
        });

        return response()->json([
            'status' => true,
            'data'   => [
                'tickets'      => $tickets,
                'pagination'   => [
                    'current_page' => $paginator->currentPage(),
                    'last_page'    => $paginator->lastPage(),
                    'per_page'     => $paginator->perPage(),
                    'total'        => $paginator->total(),
                    'from'         => $paginator->firstItem(),
                    'to'           => $paginator->lastItem(),
                ],
                'stats'        => [
                    'stat_total'      => $statTotal,
                    'stat_pending'    => $statPending,
                    'stat_on_process' => $statOnProcess,
                    'stat_resolved'   => $statResolved,
                ],
                'tab_counts'   => [
                    'all'      => $allCount,
                    'received' => $receivedCount,
                    'created'  => $createdCount,
                ],
                'current_tab'  => $tab,
                'is_admin'     => $isCompanyAdmin,
            ],
        ]);
    }

    /**
     * GET /api/mobile/support-tickets/meta
     * Fetch categories with subcategories and company users for ticket creation / filtering.
     */
    public function meta(Request $request): JsonResponse
    {
        $user = Auth::user();
        $companyId = $this->resolveCompanyId($user);
        $isCompanyAdmin = $this->checkIsCompanyAdmin($user);

        // Fetch active Categories with active subcategories
        $categories = AccountSupportCategory::when($companyId, function ($q) use ($companyId) {
                $q->where(function ($sq) use ($companyId) {
                    $sq->where('company_id', $companyId)->orWhereNull('company_id');
                });
            })
            ->active()
            ->with(['activeSubcategories' => function ($q) {
                $q->orderBy('name');
            }])
            ->orderBy('name')
            ->get()
            ->map(function ($cat) {
                return [
                    'id'            => $cat->id,
                    'name'          => $cat->name,
                    'code'          => $cat->code,
                    'description'   => $cat->description,
                    'subcategories' => $cat->activeSubcategories->map(function ($sub) {
                        return [
                            'id'             => $sub->id,
                            'name'           => $sub->name,
                            'code'           => $sub->code,
                            'description'    => $sub->description,
                            'is_expense_add' => (bool) $sub->is_expense_add,
                            'hierarchy'      => $sub->hierarchy,
                        ];
                    }),
                ];
            });

        // Get users for Select2 dropdown (exclude current user, include all active company users)
        $usersQuery = User::withoutGlobalScope('branch')
            ->where('id', '!=', $user->id)
            ->where(function ($query) {
                $query->where('is_active', true)
                    ->orWhere('user_status', 'active');
            });

        if ($companyId) {
            $usersQuery->where('company_id', $companyId);
        }

        $users = $usersQuery->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(function ($u) {
                return [
                    'id'    => $u->id,
                    'name'  => $u->name,
                    'email' => $u->email,
                ];
            });

        return response()->json([
            'status' => true,
            'data'   => [
                'categories'       => $categories,
                'users'            => $users,
                'is_company_admin' => $isCompanyAdmin,
            ],
        ]);
    }

    /**
     * GET /api/mobile/support-tickets/{id}
     * Show single ticket details.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        $isCompanyAdmin = $this->checkIsCompanyAdmin($user);

        $ticket = SupportTicket::with(['creator', 'assignedTo', 'category', 'subcategory'])->find($id);

        if (!$ticket) {
            return response()->json([
                'status'  => false,
                'message' => 'Support ticket not found.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data'   => $this->transformTicket($ticket, $user, $isCompanyAdmin),
        ]);
    }

    /**
     * POST /api/mobile/support-tickets
     * Create a new support ticket.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'subject'                        => ['required', 'string', 'max:255'],
            'message'                        => ['required', 'string'],
            'account_support_category_id'    => ['nullable', 'integer'],
            'account_support_subcategory_id' => ['nullable', 'integer'],
            'to_user_id'                     => ['nullable', 'exists:users,id'],
            'attachment'                     => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,zip,txt', 'max:10240'],
            'attachments.*'                  => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,zip,txt', 'max:10240'],
        ]);

        $user = Auth::user();
        $toUserId = $request->to_user_id;

        // Auto-resolve to_user_id if not explicitly provided using subcategory hierarchy
        if (!$toUserId && $request->filled('account_support_subcategory_id')) {
            $subcat = AccountSupportSubcategory::find($request->account_support_subcategory_id);
            if ($subcat && is_array($subcat->hierarchy)) {
                foreach ($subcat->hierarchy as $lvl) {
                    if (!empty($lvl['user_ids']) && is_array($lvl['user_ids'])) {
                        foreach ($lvl['user_ids'] as $uid) {
                            if ($uid && User::where('id', $uid)->exists()) {
                                $toUserId = (int) $uid;
                                break 2;
                            }
                        }
                    } elseif (!empty($lvl['user_id'])) {
                        if (User::where('id', $lvl['user_id'])->exists()) {
                            $toUserId = (int) $lvl['user_id'];
                            break;
                        }
                    }
                }
            }
        }

        // If still not resolved, fallback to first available active user in company
        if (!$toUserId) {
            $toUserId = User::where('id', '!=', $user->id)
                ->when($user->company_id, fn($q) => $q->where('company_id', $user->company_id))
                ->where(function ($query) {
                    $query->where('is_active', true)->orWhere('user_status', 'active');
                })
                ->value('id');
        }

        if (!$toUserId) {
            $toUserId = User::where('id', '!=', $user->id)->value('id') ?? $user->id;
        }

        $toUser = User::find($toUserId);

        // Resolve company_id with fallbacks
        $companyId = $user?->company_id;
        if (!$companyId && $user) {
            $companyId = \App\Models\EmployeeOnboarding::withoutGlobalScopes()
                ->where(function ($q) use ($user) {
                    $q->where('portal_user_id', $user->id)
                      ->orWhere('email', $user->email);
                })
                ->whereNotNull('company_id')
                ->value('company_id');
        }

        if (!$companyId && $toUser) {
            $companyId = $toUser->company_id;
        }

        if (!$companyId) {
            $companyId = \App\Models\Company::orderBy('id')->value('id');
        }

        if ($user && !$user->company_id && $companyId) {
            $user->update(['company_id' => $companyId]);
        }

        // Process file attachments (single or multiple)
        $attachmentPath = null;
        $attachmentList = [];
        $targetDir = public_path('uploads/support-tickets');
        if (!file_exists($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $filename = time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
            $file->move($targetDir, $filename);
            $attachmentPath = 'uploads/support-tickets/' . $filename;
            $attachmentList[] = $attachmentPath;
        }

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file && $file->isValid()) {
                    $filename = time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
                    $file->move($targetDir, $filename);
                    $path = 'uploads/support-tickets/' . $filename;
                    $attachmentList[] = $path;
                    if (!$attachmentPath) {
                        $attachmentPath = $path;
                    }
                }
            }
        }

        $ticket = SupportTicket::create([
            'company_id'                     => $companyId,
            'account_support_category_id'    => $request->account_support_category_id,
            'account_support_subcategory_id' => $request->account_support_subcategory_id,
            'subject'                        => $request->subject,
            'message'                        => $request->message,
            'attachment_path'                => $attachmentPath,
            'attachments'                    => !empty($attachmentList) ? $attachmentList : null,
            'created_by'                     => $user->id,
            'to_user_id'                     => $toUserId,
            'status'                         => 'pending',
        ]);

        // Email notification to assigned user
        try {
            if ($toUser && !empty($toUser->email) && filter_var($toUser->email, FILTER_VALIDATE_EMAIL)) {
                Mail::send('emails.support_ticket', [
                    'ticket'   => $ticket,
                    'toUser'   => $toUser,
                    'fromUser' => $user,
                ], function ($message) use ($ticket, $toUser) {
                    $message->to($toUser->email, $toUser->name)
                        ->subject('New Support Ticket: ' . $ticket->subject);

                    if ($ticket->attachment_path) {
                        $fullPath = public_path($ticket->attachment_path);
                        if (file_exists($fullPath)) {
                            $message->attach($fullPath);
                        } elseif (Storage::disk('public')->exists($ticket->attachment_path)) {
                            $message->attach(storage_path('app/public/' . $ticket->attachment_path));
                        }
                    }
                });
            }
        } catch (\Throwable $exception) {
            Log::error('Support Ticket mobile store email send failed.', [
                'ticket_id' => $ticket->id,
                'error'     => $exception->getMessage(),
            ]);
        }

        $ticket->load(['creator', 'assignedTo', 'category', 'subcategory']);
        $isCompanyAdmin = $this->checkIsCompanyAdmin($user);

        return response()->json([
            'status'  => true,
            'message' => 'Support ticket created successfully.',
            'data'    => $this->transformTicket($ticket, $user, $isCompanyAdmin),
        ], 201);
    }

    /**
     * POST /api/mobile/support-tickets/{id}/status
     * Update ticket status, remarks, and expense details.
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        $isCompanyAdmin = $this->checkIsCompanyAdmin($user);

        $ticket = SupportTicket::with(['creator', 'assignedTo', 'category', 'subcategory'])->find($id);

        if (!$ticket) {
            return response()->json([
                'status'  => false,
                'message' => 'Support ticket not found.',
            ], 404);
        }

        if ($ticket->to_user_id !== $user->id && !$isCompanyAdmin) {
            return response()->json([
                'status'  => false,
                'message' => 'Unauthorized. Only the assigned user or Company Admin can update ticket status.',
            ], 403);
        }

        if ($ticket->status === 'closed') {
            return response()->json([
                'status'  => false,
                'message' => 'This ticket has already been closed and cannot be updated.',
            ], 422);
        }

        $isExpenseAdd = (bool) ($ticket->subcategory?->is_expense_add ?? false);
        $isClosingOrResolving = in_array($request->status, ['resolved', 'closed'], true);

        $rules = [
            'status' => ['required', 'string', 'in:pending,onprocess,resolved,closed'],
            'remark' => ['nullable', 'string'],
        ];

        if ($isExpenseAdd && $isClosingOrResolving) {
            $rules['payment_amount'] = ['required', 'numeric', 'min:0'];
            $rules['remark'] = ['required', 'string'];
            $rules['resolution_attachment'] = ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx', 'max:25600'];
        }

        $request->validate($rules);

        $ticketData = [
            'status' => $request->status,
            'remark' => $request->remark,
        ];

        if ($isExpenseAdd && $isClosingOrResolving) {
            $ticketData['payment_amount'] = $request->payment_amount;

            if ($request->hasFile('resolution_attachment')) {
                $file = $request->file('resolution_attachment');
                $filename = 'res_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $targetDir = public_path('uploads/support_resolutions');
                if (!file_exists($targetDir)) {
                    @mkdir($targetDir, 0755, true);
                }
                $file->move($targetDir, $filename);
                $ticketData['resolution_attachment_path'] = 'uploads/support_resolutions/' . $filename;
            }
        }

        $ticket->update($ticketData);

        // Synchronize into Accounts Expense module if expense ticket is resolved or closed
        if ($isExpenseAdd && $isClosingOrResolving && $ticket->payment_amount !== null) {
            $this->syncToAccountExpense($ticket, $user);
        }

        // Send email to ticket creator and optionally cc assigned user
        try {
            $creator = $ticket->creator;
            $updater = $user;

            if ($creator && !empty($creator->email) && filter_var($creator->email, FILTER_VALIDATE_EMAIL)) {
                Mail::send('emails.support_ticket_updated', [
                    'ticket'  => $ticket,
                    'creator' => $creator,
                    'updater' => $updater,
                ], function ($message) use ($ticket, $creator, $updater) {
                    $message->to($creator->email, $creator->name)
                        ->subject('Support Ticket Updated: ' . $ticket->subject);

                    if ($ticket->assignedTo && $ticket->assignedTo->id !== $updater->id && !empty($ticket->assignedTo->email) && filter_var($ticket->assignedTo->email, FILTER_VALIDATE_EMAIL)) {
                        $message->cc($ticket->assignedTo->email, $ticket->assignedTo->name);
                    }
                });
            }
        } catch (\Throwable $exception) {
            Log::error('Support Ticket mobile update email send failed.', [
                'ticket_id' => $ticket->id,
                'error'     => $exception->getMessage(),
            ]);
        }

        $ticket->refresh()->load(['creator', 'assignedTo', 'category', 'subcategory']);

        return response()->json([
            'status'  => true,
            'message' => 'Ticket status and remarks updated successfully.',
            'data'    => $this->transformTicket($ticket, $user, $isCompanyAdmin),
        ]);
    }

    /**
     * Automatically synchronize an expense-marked support ticket into the Accounts Module (AccountExpense).
     */
    private function syncToAccountExpense(SupportTicket $ticket, User $user): ?AccountExpense
    {
        try {
            $companyId = $ticket->company_id ?: ($ticket->creator?->company_id ?: $user->company_id);

            // 1. Resolve Category and Subcategory Names
            $categoryName = $ticket->category ? trim($ticket->category->name) : 'Support & Operations';
            if (empty($categoryName)) {
                $categoryName = 'Support & Operations';
            }

            $subcategoryName = $ticket->subcategory ? trim($ticket->subcategory->name) : 'Support Expense';
            if (empty($subcategoryName)) {
                $subcategoryName = 'Support Expense';
            }

            // 2. Find or Create AccountExpenseCategory
            $accCategory = AccountExpenseCategory::withoutGlobalScopes()
                ->where(function ($q) use ($companyId) {
                    if ($companyId) {
                        $q->where('company_id', $companyId)->orWhereNull('company_id');
                    }
                })
                ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($categoryName)])
                ->first();

            if (! $accCategory) {
                $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $categoryName), 0, 4));
                if (empty($code)) {
                    $code = 'SUP';
                }

                $accCategory = AccountExpenseCategory::create([
                    'company_id'  => $companyId,
                    'name'        => $categoryName,
                    'code'        => $code,
                    'status'      => 'active',
                    'description' => 'Auto-created from Support Category: ' . $categoryName,
                    'created_by'  => $user->id,
                ]);
            }

            // 3. Find or Create AccountExpenseSubcategory
            $accSubcategory = AccountExpenseSubcategory::withoutGlobalScopes()
                ->where('account_expense_category_id', $accCategory->id)
                ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($subcategoryName)])
                ->first();

            if (! $accSubcategory) {
                $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $subcategoryName), 0, 4));
                if (empty($code)) {
                    $code = 'EXP';
                }

                $accSubcategory = AccountExpenseSubcategory::create([
                    'company_id'                  => $companyId,
                    'account_expense_category_id' => $accCategory->id,
                    'name'                        => $subcategoryName,
                    'code'                        => $code,
                    'status'                      => 'active',
                    'description'                 => 'Auto-created from Support Subcategory: ' . $subcategoryName,
                    'created_by'                  => $user->id,
                ]);
            }

            // 4. Format Attachments
            $accAttachments = [];
            if (!empty($ticket->resolution_attachment_path)) {
                $cleanPath = ltrim($ticket->resolution_attachment_path, '/');
                $fullPath = public_path($cleanPath);
                $exists = file_exists($fullPath);
                $accAttachments[] = [
                    'path'      => $cleanPath,
                    'name'      => basename($cleanPath),
                    'size'      => $exists ? @filesize($fullPath) : null,
                    'mime_type' => $exists ? @mime_content_type($fullPath) : null,
                ];
            } elseif (!empty($ticket->attachment_path)) {
                $cleanPath = ltrim($ticket->attachment_path, '/');
                $fullPath = public_path($cleanPath);
                $exists = file_exists($fullPath);
                $accAttachments[] = [
                    'path'      => $cleanPath,
                    'name'      => basename($cleanPath),
                    'size'      => $exists ? @filesize($fullPath) : null,
                    'mime_type' => $exists ? @mime_content_type($fullPath) : null,
                ];
            }

            // 5. Build rich remarks
            $effectiveAmount = (float) $ticket->payment_amount;
            $effectiveDate = now()->toDateString();

            $remarksLines = [];
            $remarksLines[] = "[Support Ticket #{$ticket->id}]";
            $remarksLines[] = "Requester: " . ($ticket->creator?->name ?? 'User') . ($ticket->creator?->email ? " ({$ticket->creator->email})" : '');
            $remarksLines[] = "Category: {$categoryName}";
            $remarksLines[] = "Subcategory: {$subcategoryName}";
            $remarksLines[] = "Subject: " . $ticket->subject;
            if (!empty($ticket->remark)) {
                $remarksLines[] = "Resolution Remarks: " . trim($ticket->remark);
            }
            $remarksLines[] = "Resolved / Closed By: {$user->name} on " . now()->format('d M Y, h:i A');
            $remarksLines[] = "Payment Amount: ₹" . number_format($effectiveAmount, 2);

            $finalRemarks = implode("\n", $remarksLines);
            $invoiceRef = "SUPPORT-TICKET-{$ticket->id}";

            // 6. Check existing AccountExpense to update or create
            $accountExpense = AccountExpense::withoutGlobalScopes()
                ->where(function ($q) use ($invoiceRef, $ticket) {
                    $q->where('invoice_no', $invoiceRef)
                      ->orWhere('remarks', 'like', "%[Support Ticket #{$ticket->id}]%");
                })
                ->first();

            if ($accountExpense) {
                $accountExpense->update([
                    'company_id'                     => $companyId,
                    'expense_date'                   => $effectiveDate,
                    'account_expense_category_id'    => $accCategory->id,
                    'account_expense_subcategory_id' => $accSubcategory?->id,
                    'amount'                         => $effectiveAmount,
                    'expense_type'                   => 'Variable',
                    'remarks'                        => $finalRemarks,
                    'attachments'                    => !empty($accAttachments) ? $accAttachments : $accountExpense->attachments,
                    'updated_by'                     => $user->id,
                ]);
            } else {
                $accountExpense = AccountExpense::create([
                    'company_id'                     => $companyId,
                    'expense_date'                   => $effectiveDate,
                    'account_expense_category_id'    => $accCategory->id,
                    'account_expense_subcategory_id' => $accSubcategory?->id,
                    'amount'                         => $effectiveAmount,
                    'expense_type'                   => 'Variable',
                    'is_recurring'                   => false,
                    'invoice_no'                     => $invoiceRef,
                    'remarks'                        => $finalRemarks,
                    'attachments'                    => $accAttachments,
                    'created_by'                     => $user->id,
                    'updated_by'                     => $user->id,
                ]);
            }

            return $accountExpense;
        } catch (\Throwable $e) {
            Log::error("Failed to sync Support Ticket #{$ticket->id} to AccountExpense: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }
}
