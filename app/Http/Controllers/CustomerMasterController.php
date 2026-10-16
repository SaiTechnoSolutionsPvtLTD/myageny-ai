<?php

namespace App\Http\Controllers;

use App\Models\CustomerMaster;
use App\Models\Invoice;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerMasterController extends Controller
{
    /**
     * Display a listing of customers with filters and statistics.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $companyId = $user?->company_id;

        $baseQuery = CustomerMaster::query()
            ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')));

        // Stats calculation
        $allCustomers = (clone $baseQuery)->get(['id', 'status']);
        $stats = [
            'total'    => $allCustomers->count(),
            'active'   => $allCustomers->where('status', 'active')->count(),
            'inactive' => $allCustomers->where('status', 'inactive')->count(),
        ];

        // Search & Filters
        $customers = (clone $baseQuery)
            ->with(['creator', 'lead'])
            ->withCount('invoices')
            ->withSum('invoices as invoices_total_amount', 'total_amount')
            ->withSum('invoices as invoices_paid_amount', 'paid_amount')
            ->withSum('invoices as invoices_balance_due', 'balance_due')
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim($request->search);
                $q->where(function ($sub) use ($search) {
                    $sub->where('customer_name', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%")
                        ->orWhere('mobile_number', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('pan_number', 'like', "%{$search}%")
                        ->orWhere('gst_number', 'like', "%{$search}%")
                        ->orWhere('billing_city', 'like', "%{$search}%")
                        ->orWhere('billing_state', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        // Preload recent converted leads for fast dropdown selection
        $convertedLeadIds = self::getConvertedLeadIds($companyId);
        $recentConvertedLeads = Lead::whereIn('id', $convertedLeadIds)
            ->latest('id')
            ->take(80)
            ->get(['id', 'contact_name', 'company_name', 'mobile_number', 'email']);

        return view('pages.accounts.master.customers', compact('customers', 'stats', 'recentConvertedLeads'));
    }

    /**
     * Search converted leads by Name or Mobile number for live autocomplete / search.
     */
    public function searchConvertedLeads(Request $request)
    {
        $user = auth()->user();
        $companyId = $user?->company_id;
        $q = trim($request->input('q', ''));

        $convertedLeadIds = self::getConvertedLeadIds($companyId);

        $leads = Lead::whereIn('id', $convertedLeadIds)
            ->when($q, function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('contact_name', 'like', "%{$q}%")
                        ->orWhere('mobile_number', 'like', "%{$q}%")
                        ->orWhere('company_name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->latest('id')
            ->take(30)
            ->get(['id', 'contact_name', 'company_name', 'mobile_number', 'email']);

        $results = $leads->map(function ($lead) {
            $displayName = $lead->contact_name ?: ($lead->company_name ?: 'Lead #' . $lead->id);
            $subText = array_filter([$lead->company_name, $lead->mobile_number, $lead->email]);
            return [
                'id'            => $lead->id,
                'name'          => $displayName,
                'mobile_number' => $lead->mobile_number,
                'company_name'  => $lead->company_name,
                'email'         => $lead->email,
                'label'         => $displayName . (!empty($subText) ? ' (' . implode(' • ', $subText) . ')' : ''),
            ];
        });

        return response()->json($results);
    }

    /**
     * Get auto-fill details of a converted lead.
     */
    public function getLeadDetails(Lead $lead)
    {
        $details = self::extractLeadDetails($lead);
        return response()->json($details);
    }

    /**
     * Display the specified customer details along with invoice and payment collection history.
     */
    public function show(CustomerMaster $customer)
    {
        $invoices = Invoice::where('customer_id', $customer->id)
            ->latest('invoice_date')
            ->latest('id')
            ->get();

        $totalInvoiced   = (float) $invoices->sum('total_amount');
        $totalCollected  = (float) $invoices->sum('paid_amount');
        $totalBalanceDue = (float) $invoices->sum('balance_due');
        $invoicesCount   = $invoices->count();

        $invoiceData = $invoices->map(function ($inv) {
            return [
                'id'              => $inv->id,
                'invoice_number'  => $inv->invoice_number,
                'invoice_date'    => $inv->invoice_date ? $inv->invoice_date->format('d M Y') : '—',
                'due_date'        => $inv->due_date ? $inv->due_date->format('d M Y') : '—',
                'subtotal'        => (float) $inv->subtotal,
                'total_tax'       => (float) $inv->total_tax,
                'total_amount'    => (float) $inv->total_amount,
                'paid_amount'     => (float) $inv->paid_amount,
                'balance_due'     => (float) $inv->balance_due,
                'status'          => $inv->status,
                'status_badge'    => $inv->status_badge,
                'total_formatted' => '₹ ' . number_format((float) $inv->total_amount, 2),
                'paid_formatted'  => '₹ ' . number_format((float) $inv->paid_amount, 2),
                'due_formatted'   => '₹ ' . number_format((float) $inv->balance_due, 2),
                'view_url'        => route('accounts.sales.invoices.show', $inv->id),
                'print_url'       => route('accounts.sales.invoices.print', $inv->id),
            ];
        });

        return response()->json([
            'customer' => [
                'id'                        => $customer->id,
                'customer_name'             => $customer->customer_name,
                'company_name'              => $customer->company_name,
                'mobile_number'             => $customer->mobile_number,
                'email'                     => $customer->email,
                'pan_number'                => $customer->pan_number,
                'gst_number'                => $customer->gst_number,
                'payment_terms'             => $customer->payment_terms,
                'status'                    => $customer->status,
                'remarks'                   => $customer->remarks,
                'lead_id'                   => $customer->lead_id,
                'created_at'                => $customer->created_at ? $customer->created_at->format('d M Y, h:i A') : '—',
                'billing_attention'         => $customer->billing_attention,
                'billing_country'           => $customer->billing_country ?: 'India',
                'billing_address_street_1'  => $customer->billing_address_street_1,
                'billing_address_street_2'  => $customer->billing_address_street_2,
                'billing_city'              => $customer->billing_city,
                'billing_state'             => $customer->billing_state,
                'billing_pin_code'          => $customer->billing_pin_code,
                'full_billing_address'      => $customer->full_billing_address,
                'shipping_attention'        => $customer->shipping_attention,
                'shipping_country'          => $customer->shipping_country ?: 'India',
                'shipping_address_street_1' => $customer->shipping_address_street_1,
                'shipping_address_street_2' => $customer->shipping_address_street_2,
                'shipping_city'             => $customer->shipping_city,
                'shipping_state'            => $customer->shipping_state,
                'shipping_pin_code'         => $customer->shipping_pin_code,
                'full_shipping_address'     => $customer->full_shipping_address,
            ],
            'financials' => [
                'invoices_count'            => $invoicesCount,
                'total_invoiced_amount'     => $totalInvoiced,
                'total_collected_amount'    => $totalCollected,
                'total_balance_due'         => $totalBalanceDue,
                'total_invoiced_formatted'  => '₹ ' . number_format($totalInvoiced, 2),
                'total_collected_formatted' => '₹ ' . number_format($totalCollected, 2),
                'total_balance_formatted'   => '₹ ' . number_format($totalBalanceDue, 2),
            ],
            'invoices' => $invoiceData,
            'create_invoice_url' => route('accounts.sales.invoices.create', ['customer_id' => $customer->id]),
        ]);
    }

    /**
     * Store a newly created customer in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name'              => 'required|string|max:255',
            'company_name'               => 'nullable|string|max:255',
            'mobile_number'              => 'nullable|string|max:50',
            'email'                      => 'nullable|email|max:255',
            'pan_number'                 => 'nullable|string|max:30',
            'gst_number'                 => 'nullable|string|max:50',
            'payment_terms'              => 'nullable|string|max:100',
            'lead_id'                    => 'nullable|integer',

            // Billing Address
            'billing_attention'          => 'nullable|string|max:255',
            'billing_country'            => 'nullable|string|max:100',
            'billing_address_street_1'   => 'nullable|string',
            'billing_address_street_2'   => 'nullable|string',
            'billing_city'               => 'nullable|string|max:100',
            'billing_state'              => 'nullable|string|max:100',
            'billing_pin_code'           => 'nullable|string|max:20',

            // Shipping Address
            'shipping_attention'         => 'nullable|string|max:255',
            'shipping_country'           => 'nullable|string|max:100',
            'shipping_address_street_1'  => 'nullable|string',
            'shipping_address_street_2'  => 'nullable|string',
            'shipping_city'              => 'nullable|string|max:100',
            'shipping_state'             => 'nullable|string|max:100',
            'shipping_pin_code'          => 'nullable|string|max:20',

            'remarks'                    => 'nullable|string',
        ]);

        $companyId = auth()->user()?->company_id;
        $userId    = auth()->id();

        // Prevent duplicate creation if an identical customer was submitted within the last 15 seconds
        $existingRecent = CustomerMaster::where('company_id', $companyId)
            ->where('customer_name', trim($validated['customer_name']))
            ->when(!empty($validated['mobile_number']), function ($q) use ($validated) {
                $q->where('mobile_number', trim($validated['mobile_number']));
            })
            ->when(!empty($validated['email']), function ($q) use ($validated) {
                $q->where('email', trim($validated['email']));
            })
            ->where('created_at', '>=', now()->subSeconds(15))
            ->first();

        if ($existingRecent) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Customer already exists.',
                    'customer' => $existingRecent,
                ]);
            }
            return redirect()->back()->with('success', 'Customer created successfully!');
        }

        $validated['company_id'] = $companyId;
        $validated['status']     = 'active';
        $validated['created_by'] = $userId;

        // Default countries if blank
        $validated['billing_country']  = $validated['billing_country'] ?: 'India';
        $validated['shipping_country'] = $validated['shipping_country'] ?: 'India';

        $customer = CustomerMaster::create($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Customer created successfully!',
                'customer' => $customer,
            ]);
        }

        return redirect()->back()->with('success', 'Customer created successfully!');
    }

    /**
     * Update the specified customer in storage.
     */
    public function update(Request $request, CustomerMaster $customer)
    {
        $validated = $request->validate([
            'customer_name'              => 'required|string|max:255',
            'company_name'               => 'nullable|string|max:255',
            'mobile_number'              => 'nullable|string|max:50',
            'email'                      => 'nullable|email|max:255',
            'pan_number'                 => 'nullable|string|max:30',
            'gst_number'                 => 'nullable|string|max:50',
            'payment_terms'              => 'nullable|string|max:100',
            'lead_id'                    => 'nullable|integer',

            // Billing Address
            'billing_attention'          => 'nullable|string|max:255',
            'billing_country'            => 'nullable|string|max:100',
            'billing_address_street_1'   => 'nullable|string',
            'billing_address_street_2'   => 'nullable|string',
            'billing_city'               => 'nullable|string|max:100',
            'billing_state'              => 'nullable|string|max:100',
            'billing_pin_code'           => 'nullable|string|max:20',

            // Shipping Address
            'shipping_attention'         => 'nullable|string|max:255',
            'shipping_country'           => 'nullable|string|max:100',
            'shipping_address_street_1'  => 'nullable|string',
            'shipping_address_street_2'  => 'nullable|string',
            'shipping_city'              => 'nullable|string|max:100',
            'shipping_state'             => 'nullable|string|max:100',
            'shipping_pin_code'          => 'nullable|string|max:20',

            'remarks'                    => 'nullable|string',
            'status'                     => 'required|in:active,inactive',
        ]);

        $customer->update($validated);

        return redirect()->back()->with('success', 'Customer updated successfully!');
    }

    /**
     * Toggle customer status between active and inactive.
     */
    public function toggleStatus(CustomerMaster $customer)
    {
        $newStatus = $customer->status === 'active' ? 'inactive' : 'active';
        $customer->update(['status' => $newStatus]);

        return redirect()->back()->with('success', "Customer status changed to {$newStatus}.");
    }

    /**
     * Remove the specified customer from storage (Soft Delete).
     */
    public function destroy(CustomerMaster $customer)
    {
        $customer->delete();

        return redirect()->back()->with('success', 'Customer deleted successfully!');
    }

    /**
     * Helper to retrieve all lead IDs marked as converted in the system.
     */
    public static function getConvertedLeadIds(?int $companyId = null): array
    {
        $convertedFromProducts = DB::table('lead_products')
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where(function ($q) {
                $q->whereRaw('LOWER(product_status) = ?', ['converted'])
                  ->orWhereNotNull('converted_at');
            })
            ->whereNotNull('lead_id')
            ->pluck('lead_id')
            ->toArray();

        $convertedStatusIds = DB::table('lead_statuses')
            ->whereRaw('LOWER(name) = ?', ['converted'])
            ->pluck('id')
            ->toArray();

        $convertedFromLeads = DB::table('leads')
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where(function ($q) use ($convertedStatusIds) {
                if (!empty($convertedStatusIds)) {
                    $q->whereIn('lead_status_id', $convertedStatusIds);
                }
                $q->orWhereRaw('LOWER(lead_status) = ?', ['converted']);
            })
            ->pluck('id')
            ->toArray();

        return array_values(array_unique(array_merge($convertedFromProducts, $convertedFromLeads)));
    }

    /**
     * Helper to extract auto-fill fields from a Lead.
     */
    public static function extractLeadDetails(Lead $lead): array
    {
        $fieldValues = DB::table('lead_field_values')
            ->join('lead_form_fields', 'lead_field_values.lead_form_field_id', '=', 'lead_form_fields.id')
            ->where('lead_field_values.lead_id', $lead->id)
            ->get(['lead_form_fields.field_name', 'lead_form_fields.label', 'lead_field_values.value']);

        $city = null;
        $address = null;
        $state = null;

        foreach ($fieldValues as $fv) {
            $name = strtolower($fv->field_name . ' ' . $fv->label);
            if (str_contains($name, 'city') && !$city) {
                $city = trim($fv->value);
            }
            if ((str_contains($name, 'address') || str_contains($name, 'location') || str_contains($name, 'area')) && !$address) {
                $address = trim($fv->value);
            }
            if (str_contains($name, 'state') && !$state) {
                $state = trim($fv->value);
            }
        }

        if (is_array($lead->facebook_payload) && isset($lead->facebook_payload['field_data'])) {
            foreach ($lead->facebook_payload['field_data'] as $fd) {
                $fieldName = strtolower($fd['name'] ?? '');
                $val = $fd['values'][0] ?? null;
                if ($val) {
                    if (str_contains($fieldName, 'city') && !$city) {
                        $city = trim($val);
                    }
                    if ((str_contains($fieldName, 'street') || str_contains($fieldName, 'address') || str_contains($fieldName, 'location')) && !$address) {
                        $address = trim($val);
                    }
                    if (str_contains($fieldName, 'state') && !$state) {
                        $state = trim($val);
                    }
                }
            }
        }

        return [
            'id'            => $lead->id,
            'customer_name' => $lead->contact_name ?: $lead->company_name,
            'company_name'  => $lead->company_name,
            'mobile_number' => $lead->mobile_number,
            'email'         => $lead->email,
            'city'          => $city,
            'address'       => $address,
            'state'         => $state ?: 'Tamil Nadu',
        ];
    }
}
