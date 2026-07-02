<?php
// ================================================================
// FILE: app/Http/Controllers/Lead/LeadShowController.php
// Handles all sub-resource actions on the Lead show page:
//   Call Updates, Reminders, Products, Quotations
// ================================================================

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadCallUpdate;
use App\Models\LeadProduct;
use App\Models\LeadReminder;
use App\Models\LeadStatus;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Services\DataVisibilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeadShowController extends Controller
{
    public function __construct(private readonly DataVisibilityService $visibility) {}

    // ════════════════════════════════════════
    // CALL UPDATES
    // ════════════════════════════════════════

    public function storeCall(Request $request, Lead $lead)
    {
        abort_unless($this->visibility->canAccessLead($lead), 403);

        $data = $request->validate([
            // 'called_at'        => ['required', 'date'],
            // 'call_type'        => ['required', 'in:outgoing,incoming,missed'],
            // 'duration_minutes' => ['nullable', 'integer', 'min:0'],
            'outcome'          => ['required'],
            'outcome_sub_category_id'          => ['required'],
            'notes'            => ['nullable', 'string', 'max:1000'],
            'next_follow_up'   => ['nullable', 'date', 'after_or_equal:today'],
            'followup_time'   => ['required'],
        ]);

        $data['lead_id'] = $lead->id;
        $data['called_at'] = date("Y-m-d H:i:s");
        $data['outcome_subcategory'] = $request->outcome_sub_category_id;
        $data['user_id'] = auth()->id();
        $data['company_id'] = $lead->company_id;

        LeadCallUpdate::create($data);

        return back()->with('success', 'Call update added successfully.');
    }

    public function destroyCall(Lead $lead, LeadCallUpdate $call)
    {
        abort_unless($this->visibility->canAccessLead($lead), 403);
        abort_if($call->lead_id !== $lead->id, 403);
        $call->delete();
        return back()->with('success', 'Call record removed.');
    }

    // ════════════════════════════════════════
    // REMINDERS
    // ════════════════════════════════════════

    public function storeReminder(Request $request, Lead $lead)
    {
        abort_unless($this->visibility->canAccessLead($lead), 403);

        $data = $request->validate([
            'title'       => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'remind_at'   => ['required', 'date'],
            'type'        => ['required', 'in:' . implode(',', array_keys(LeadReminder::TYPES))],
            'priority'    => ['required', 'in:low,medium,high'],
            'remainder_time'    => ['required'],
        ]);

        $data['lead_id'] = $lead->id;
        $data['user_id'] = auth()->id();

        LeadReminder::create($data);

        return back()->with('success', 'Reminder set successfully.');
    }

    public function completeReminder(Lead $lead, LeadReminder $reminder)
    {
        abort_unless($this->visibility->canAccessLead($lead), 403);
        abort_if($reminder->lead_id !== $lead->id, 403);
        $reminder->update(['is_completed' => true, 'completed_at' => now()]);
        return back()->with('success', 'Reminder marked as completed.');
    }

    public function destroyReminder(Lead $lead, LeadReminder $reminder)
    {
        abort_unless($this->visibility->canAccessLead($lead), 403);
        abort_if($reminder->lead_id !== $lead->id, 403);
        $reminder->delete();
        return back()->with('success', 'Reminder removed.');
    }

    // ════════════════════════════════════════
    // LEAD PRODUCTS
    // ════════════════════════════════════════

     public function storeProduct(Request $request, Lead $lead)
    {
        abort_unless($this->visibility->canAccessLead($lead), 403);



        $data = $request->validate([
            'product_id'      => ['required', 'integer', 'exists:products,id'],
            'product_status'  => ['required', 'in:new,hot,warm,cold,converted'],
            'description'     => ['nullable', 'string', 'max:500'],
            'unit_price'      => ['required', 'numeric', 'min:0'],
            'quantity'        => ['required', 'integer', 'min:1'],
            'discount_percent'=> ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $catalogProduct = Product::with('category')->findOrFail($data['product_id']);
        $data['lead_id']          = $lead->id;
        $data['product_name']     = $this->leadProductName($catalogProduct);
        $data['discount_percent'] = $data['discount_percent'] ?? 0;
        $data['payment_status']   = 'pending';
        $data['company_id']       = $lead->company_id;

        \App\Models\LeadProduct::create($data);

        return back()->with('success', "Product <strong>{$data['product_name']}</strong> added.");
    }

    public function updateProductStatus(Request $request, Lead $lead, \App\Models\LeadProduct $product)
    {
        abort_unless($this->visibility->canAccessLead($lead), 403);
        abort_if($product->lead_id !== $lead->id, 403);

        $request->validate([
            'lead_status_id' => ['nullable', 'integer', 'exists:lead_statuses,id'],
            'product_status' => ['nullable', 'string'],
        ]);

        $companyId = $lead->company_id ?? Auth::user()?->company_id;
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

        $status = $request->filled('lead_status_id')
            ? $statuses->firstWhere('id', (int) $request->lead_status_id)
            : $statuses->first(fn ($option) => LeadProduct::statusKey($option->name) === LeadProduct::statusKey($request->product_status));

        if (! $status) {
            return back()->withErrors(['lead_status_id' => 'Please select a valid lead status.']);
        }

        $product->update([
            'lead_status_id' => $status->id,
            'product_status' => LeadProduct::statusKey($status->name),
        ]);

        return back()->with('success', "Product status updated to <strong>{$status->name}</strong>.");
    }

     public function storeProductPayment(Request $request, Lead $lead, \App\Models\LeadProduct $product)
    {
        abort_unless($this->visibility->canAccessLead($lead), 403);
        abort_if($product->lead_id !== $lead->id, 403);

        if ($product->product_status_key !== 'converted') {
            return back()->with('error', 'Payments can be added only after the product status is Converted.');
        }

        $data = $request->validate([
            'amount'           => ['required', 'numeric', 'min:0.01'],
            'payment_mode'     => ['required', 'string', 'in:cash,bank_transfer,cheque,upi,card'],
            'payment_date'     => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes'            => ['nullable', 'string', 'max:500'],
        ]);

        $data['lead_product_id'] = $product->id;
        $data['lead_id']         = $lead->id;
        $data['recorded_by']     = auth()->id();

        \App\Models\LeadProductPayment::create($data);

        // Sync payment status on product
        $product->syncPaymentStatus();

        return back()->with('success', "Payment of ₹" . number_format($data['amount'], 2) . " recorded.");
    }

    /**
     * Delete a product payment entry.
     */
    public function destroyProductPayment(Lead $lead, \App\Models\LeadProduct $product, \App\Models\LeadProductPayment $payment)
    {
        abort_unless($this->visibility->canAccessLead($lead), 403);
        abort_if($payment->lead_product_id !== $product->id, 403);

        $payment->delete();
        $product->syncPaymentStatus();

        return back()->with('success', 'Payment entry removed.');
    }

    /**
     * Destroy a product.
     */
    public function destroyProduct(Lead $lead, \App\Models\LeadProduct $product)
    {
        abort_unless($this->visibility->canAccessLead($lead), 403);
        abort_if($product->lead_id !== $lead->id, 403);
        $product->delete();
        return back()->with('success', 'Product removed from lead.');
    }

    public function updateProduct(Request $request, Lead $lead, LeadProduct $product)
    {
        abort_unless($this->visibility->canAccessLead($lead), 403);
        abort_if($product->lead_id !== $lead->id, 403);

        $data = $request->validate([
            'product_id'        => ['required', 'integer', 'exists:products,id'],
            'product_status'    => ['required', 'in:new,hot,warm,cold,converted'],
            'description'       => ['nullable', 'string', 'max:500'],
            'unit_price'        => ['required', 'numeric', 'min:0'],
            'quantity'          => ['required', 'integer', 'min:1'],
            'discount_percent'  => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $catalogProduct = Product::with('category')->findOrFail($data['product_id']);
        $data['product_name'] = $this->leadProductName($catalogProduct);
        $data['discount_percent'] = $data['discount_percent'] ?? 0;
        $data['lead_status_id'] = null;

        $product->update($data);

        return back()->with('success', 'Product updated.');
    }

    private function leadProductName(Product $product): string
    {
        return $product->product_name
            ?: trim(($product->category?->name ? $product->category->name . ' | ' : '') . $product->package_name);
    }



    // ════════════════════════════════════════
    // QUOTATIONS
    // ════════════════════════════════════════

    public function storeQuotation(Request $request, Lead $lead)
    {
        abort_unless($this->visibility->canAccessLead($lead), 403);

        $data = $request->validate([
            'quotation_date'   => ['required', 'date'],
            'valid_until'      => ['nullable', 'date', 'after_or_equal:quotation_date'],
            'discount_amount'  => ['nullable', 'numeric', 'min:0'],
            'tax_percent'      => ['nullable', 'numeric', 'min:0', 'max:100'],
            'terms_conditions' => ['nullable', 'string'],
            'notes'            => ['nullable', 'string', 'max:1000'],
            // Line items
            'items'            => ['required', 'array', 'min:1'],
            'items.*.product_name'    => ['required', 'string', 'max:150'],
            'items.*.description'     => ['nullable', 'string'],
            'items.*.quantity'        => ['required', 'integer', 'min:1'],
            'items.*.unit'            => ['nullable', 'string', 'max:20'],
            'items.*.unit_price'      => ['required', 'numeric', 'min:0'],
            'items.*.discount_percent'=> ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $quotation = Quotation::create([
            'lead_id'          => $lead->id,
            'company_id'       => $lead->company_id,
            'created_by'       => auth()->id(),
            'quotation_date'   => $data['quotation_date'],
            'valid_until'      => $data['valid_until'] ?? null,
            'discount_amount'  => $data['discount_amount'] ?? 0,
            'tax_percent'      => $data['tax_percent'] ?? 0,
            'terms_conditions' => $data['terms_conditions'] ?? null,
            'notes'            => $data['notes'] ?? null,
            'status'           => 'draft',
            'subtotal'         => 0, 'tax_amount' => 0, 'grand_total' => 0,
        ]);

        foreach ($data['items'] as $i => $item) {
            QuotationItem::create([
                'quotation_id'     => $quotation->id,
                'company_id'       => $lead->company_id,
                'product_name'     => $item['product_name'],
                'description'      => $item['description'] ?? null,
                'quantity'         => $item['quantity'],
                'unit'             => $item['unit'] ?? 'Nos',
                'unit_price'       => $item['unit_price'],
                'discount_percent' => $item['discount_percent'] ?? 0,
                'sort_order'       => $i,
            ]);
        }

        $quotation->refresh()->recalculateTotals();

        return back()->with('success', "Quotation <strong>{$quotation->quotation_number}</strong> created.");
    }

    public function updateQuotationStatus(Request $request, Lead $lead, Quotation $quotation)
    {
        abort_unless($this->visibility->canAccessLead($lead), 403);
        abort_if($quotation->lead_id !== $lead->id, 403);

        $request->validate([
            'status' => ['required', 'in:' . implode(',', array_keys(Quotation::STATUSES))],
        ]);

        $quotation->update(['status' => $request->status]);

        return back()->with('success', "Quotation status updated to <strong>{$quotation->status_label}</strong>.");
    }

    public function destroyQuotation(Lead $lead, Quotation $quotation)
    {
        abort_unless($this->visibility->canAccessLead($lead), 403);
        abort_if($quotation->lead_id !== $lead->id, 403);
        $quotation->delete();
        return back()->with('success', 'Quotation deleted.');
    }
}
