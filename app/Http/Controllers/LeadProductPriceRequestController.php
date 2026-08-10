<?php

namespace App\Http\Controllers;

use App\Models\LeadProduct;
use App\Models\LeadProductPriceRequest;
use App\Models\LeadStatus;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class LeadProductPriceRequestController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'lead_id' => ['required', 'exists:leads,id'],
            'deal_name' => ['required', 'string', 'max:255'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.product_id' => ['required', 'exists:products,id'],
            'products.*.requested_unit_price' => ['required', 'numeric', 'min:0'],
            'products.*.quantity' => ['required', 'integer', 'min:1'],
            'products.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'products.*.remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $created = DB::transaction(function () use ($request) {
            $rows = [];

            foreach ($request->products as $row) {
                $product = Product::findOrFail($row['product_id']);

                $rows[] = LeadProductPriceRequest::create([
                    'lead_id' => $request->lead_id,
                    'product_id' => $product->id,
                    'deal_name' => $request->deal_name,
                    'product_name' => $product->package_name,
                    'product_description' => $product->description,
                    'original_unit_price' => (float) $product->final_price,
                    'requested_unit_price' => (float) $row['requested_unit_price'],
                    'quantity' => (int) $row['quantity'],
                    'discount_percent' => (float) ($row['discount_percent'] ?? 0),
                    'remarks' => $row['remarks'] ?? null,
                    'status' => 'pending',
                    'requested_by' => auth()->id(),
                ]);
            }

            return $rows;
        });

        // Send email notification to tamilarasan@saitechnosolutions.net
        try {
            $recipientEmail = 'tamilarasan@saitechnosolutions.net';
            // $recipientEmail = 'kesavaraj@saitechnosolutions.net';
            $lead = \App\Models\Lead::find($request->lead_id);
            $requestedBy = auth()->user();

            \Illuminate\Support\Facades\Mail::send('emails.price_request', [
                'priceRequests' => $created,
                'lead' => $lead,
                'dealName' => $request->deal_name,
                'requestedBy' => $requestedBy,
            ], function ($message) use ($recipientEmail, $lead, $request) {
                $message->to($recipientEmail, 'Tamilarasan')
                    ->subject('New Price Request for Lead #' . $request->lead_id . ' (' . ($lead?->company_name ?: $lead?->contact_name ?: 'Lead') . ')');
            });
        } catch (\Throwable $exception) {
            \Illuminate\Support\Facades\Log::error('Price Request email notification failed.', [
                'lead_id' => $request->lead_id,
                'error' => $exception->getMessage(),
            ]);
        }

        return response()->json([
            'message' => 'Price change request sent for admin approval.',
            'data' => $created,
        ], 201);
    }

    public function index(Request $request): View
    {
        // abort_unless($this->isAdmin($request->user()), 403);

        $query = LeadProductPriceRequest::with(['lead', 'product', 'requestedBy', 'approvedBy'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('lead_id')) {
            $query->where('lead_id', $request->lead_id);
        }

        if ($request->filled('requested_by')) {
            $query->where('requested_by', $request->requested_by);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $requests = $query->paginate(15)->withQueryString();
        $requesters = User::orderBy('name')->get(['id', 'name']);

        return view('pages.leads.price_requests.index', compact('requests', 'requesters'));
    }

    public function approve(Request $request, LeadProductPriceRequest $priceRequest): RedirectResponse
    {

        // abort_unless($this->isAdmin($request->user()), 403);

        if ($priceRequest->status !== 'pending') {
            return back()->with('error', 'This request has already been processed.');
        }

        DB::transaction(function () use ($request, $priceRequest) {
            $lead = $priceRequest->lead;
            $companyId = $lead?->company_id ?? $request->user()?->company_id;
            $statuses = LeadStatus::query()
                ->when(
                    $companyId,
                    fn ($query) => $query->where(fn ($statusQuery) => $statusQuery
                        ->where('company_id', $companyId)
                        ->orWhereNull('company_id')),
                    fn ($query) => $query->whereNull('company_id')
                )
                ->orderBy('name')
                ->get();
            $defaultStatus = $statuses->first(fn ($status) => LeadProduct::statusKey($status->name) === 'new')
                ?? $statuses->first();

            $leadProduct = LeadProduct::create([
                'lead_id' => $priceRequest->lead_id,
                'product_id' => $priceRequest->product_id,
                'deal_name' => $priceRequest->deal_name,
                'product_name' => $priceRequest->product_name,
                'description' => $priceRequest->product_description,
                'unit_price' => $priceRequest->requested_unit_price,
                'quantity' => $priceRequest->quantity,
                'discount_percent' => $priceRequest->discount_percent,
                'remarks' => $priceRequest->remarks,
                'product_status' => LeadProduct::statusKey($defaultStatus?->name ?? 'new'),
                'lead_status_id' => $defaultStatus?->id,
                'created_by' => $priceRequest->requested_by,
            ]);

            $priceRequest->update([
                'status' => 'approved',
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
                'lead_product_id' => $leadProduct->id,
                'rejection_reason' => null,
            ]);
        });

        $this->sendStatusNotificationMail($priceRequest, $request->user());

        return back()->with('success', 'Price request approved and product added to the lead.');
    }

    public function reject(Request $request, LeadProductPriceRequest $priceRequest): RedirectResponse
    {
        // abort_unless($this->isAdmin($request->user()), 403);

        if ($priceRequest->status !== 'pending') {
            return back()->with('error', 'This request has already been processed.');
        }

        $data = $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $priceRequest->update([
            'status' => 'rejected',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'rejection_reason' => $data['rejection_reason'] ?? null,
        ]);

        $this->sendStatusNotificationMail($priceRequest, $request->user());

        return back()->with('success', 'Price request rejected.');
    }

    private function sendStatusNotificationMail(LeadProductPriceRequest $priceRequest, ?User $actionBy): void
    {
        try {
            $priceRequest->loadMissing(['lead.assignedTo', 'requestedBy']);

            $assignedUser = $priceRequest->lead?->assignedTo;
            $requestedUser = $priceRequest->requestedBy;

            $usersToNotify = collect([$assignedUser, $requestedUser])
                ->filter()
                ->unique('id');

            foreach ($usersToNotify as $user) {
                if (empty($user->email) || !filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }

                Mail::send('emails.price_request_status', [
                    'priceRequest' => $priceRequest,
                    'recipientName' => $user->name,
                    'actionBy' => $actionBy,
                ], function ($message) use ($user, $priceRequest) {
                    $statusTitle = ucfirst($priceRequest->status);
                    $message->to($user->email, $user->name)
                        ->subject("Price Change Request {$statusTitle} - Lead #{$priceRequest->lead_id} ({$priceRequest->deal_name})");
                });
            }
        } catch (\Throwable $exception) {
            \Illuminate\Support\Facades\Log::error('Failed to send price request status email.', [
                'price_request_id' => $priceRequest->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    protected function isAdmin(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $user->hasCrmPermission('price_requests.approve');
    }
}
