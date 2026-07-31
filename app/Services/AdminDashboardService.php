<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadProduct;
use App\Models\LeadProductPayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminDashboardService
{
    public function __construct(private readonly DataVisibilityService $visibility) {}

    /**
     * Build base query for lead_products with all filters applied.
     */
    private function baseQuery(array $filters)
    {
        $user = auth()->user();
        $query = LeadProduct::query()
            ->join('leads', 'leads.id', '=', 'lead_products.lead_id')
            ->leftJoin('products', 'products.id', '=', 'lead_products.product_id')
            ->leftJoin('users', 'users.id', '=', 'leads.assigned_to')
            ->leftJoin('branches', 'branches.id', '=', 'users.branch_id');

        $visibleUserIds = $this->visibility->visibleUserIds($user);
        $this->visibility->applyCompanyVisibility($query, $user, 'leads.company_id');

        if ($visibleUserIds !== null) {
            $query->whereIn('leads.assigned_to', $visibleUserIds);
        }

        if ($user && ($user->isBranchAdmin() || $this->visibility->hasBranchAdminRole($user))) {
            $branchIds = $user->getMyBranchIds();
            if (!empty($branchIds)) {
                $query->where(function ($q) use ($branchIds) {
                    $q->whereIn('leads.branch_id', $branchIds)
                      ->orWhereIn('users.branch_id', $branchIds);
                });
            }
        }

        // Product filter
        if (!empty($filters['product_id'])) {
            $query->where('lead_products.product_id', $filters['product_id']);
        }

        // Lead filter
        if (!empty($filters['lead_id'])) {
            $query->where('lead_products.lead_id', $filters['lead_id']);
        }

        // Branch filter
        if (!empty($filters['branch_id'])) {
            $query->where('users.branch_id', $filters['branch_id']);
        }

        // User filter
        if (!empty($filters['user_id'])) {
            $query->where('leads.assigned_to', $filters['user_id']);
        }

        // Source filter
        if (!empty($filters['source'])) {
            $query->where('leads.lead_source', $filters['source']);
        }

        // Status filter
        if (!empty($filters['status'])) {
            $query->where('lead_products.product_status', $filters['status']);
        }

        // Date range filter
        if (!empty($filters['from_date'])) {
            $query->whereDate('lead_products.created_at', '>=', Carbon::parse($filters['from_date'])->startOfDay());
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('lead_products.created_at', '<=', Carbon::parse($filters['to_date'])->endOfDay());
        }

        return $query;
    }

    /**
     * Base payment query with filters applied via lead_products join.
     */
    private function paymentBaseQuery(array $filters)
    {
        $user = auth()->user();
        $query = LeadProductPayment::query()
            ->join('lead_products', 'lead_products.id', '=', 'lead_product_payments.lead_product_id')
            ->join('leads', 'leads.id', '=', 'lead_products.lead_id')
            ->leftJoin('products', 'products.id', '=', 'lead_products.product_id')
            ->leftJoin('users', 'users.id', '=', 'leads.assigned_to')
            ->leftJoin('branches', 'branches.id', '=', 'users.branch_id');

        $visibleUserIds = $this->visibility->visibleUserIds($user);
        $this->visibility->applyCompanyVisibility($query, $user, 'leads.company_id');

        if ($visibleUserIds !== null) {
            $query->whereIn('leads.assigned_to', $visibleUserIds);
        }

        if ($user && ($user->isBranchAdmin() || $this->visibility->hasBranchAdminRole($user))) {
            $branchIds = $user->getMyBranchIds();
            if (!empty($branchIds)) {
                $query->where(function ($q) use ($branchIds) {
                    $q->whereIn('leads.branch_id', $branchIds)
                      ->orWhereIn('users.branch_id', $branchIds);
                });
            }
        }

        if (!empty($filters['product_id'])) {
            $query->where('lead_products.product_id', $filters['product_id']);
        }

        if (!empty($filters['lead_id'])) {
            $query->where('lead_products.lead_id', $filters['lead_id']);
        }

        if (!empty($filters['branch_id'])) {
            $query->where('users.branch_id', $filters['branch_id']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('leads.assigned_to', $filters['user_id']);
        }

        if (!empty($filters['source'])) {
            $query->where('leads.lead_source', $filters['source']);
        }

        if (!empty($filters['status'])) {
            $query->where('lead_products.product_status', $filters['status']);
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('lead_product_payments.payment_date', '>=', Carbon::parse($filters['from_date'])->startOfDay());
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('lead_product_payments.payment_date', '<=', Carbon::parse($filters['to_date'])->endOfDay());
        }

        return $query;
    }

    /**
     * 1. Product Summary Card.
     */
    public function getProductSummary(array $filters): array
    {
        $base = $this->baseQuery($filters);

        $results = (clone $base)
            ->selectRaw("
                COUNT(*) as total_products,
                SUM(CASE WHEN lead_products.product_status = 'converted' THEN 1 ELSE 0 END) as converted_products,
                SUM(CASE WHEN lead_products.product_status = 'hot' THEN 1 ELSE 0 END) as hot_products,
                SUM(CASE WHEN lead_products.product_status = 'cold' THEN 1 ELSE 0 END) as cold_products
            ")
            ->first();

        return [
            'total_products'     => (int) ($results->total_products ?? 0),
            'converted_products' => (int) ($results->converted_products ?? 0),
            'hot_products'       => (int) ($results->hot_products ?? 0),
            'cold_products'      => (int) ($results->cold_products ?? 0),
        ];
    }

    /**
     * 2. Product Value Summary Card.
     */
    public function getValueSummary(array $filters): array
    {
        $base = $this->baseQuery($filters);

        $productValues = (clone $base)
            ->selectRaw("
                SUM(lead_products.total_price) as total_products_value,
                SUM(CASE WHEN lead_products.product_status = 'converted' THEN lead_products.total_price ELSE 0 END) as converted_products_value
            ")
            ->first();

        $receivedValue = $this->paymentBaseQuery($filters)
            ->sum('lead_product_payments.amount');

        $totalValue = (float) ($productValues->total_products_value ?? 0);
        $received   = (float) ($receivedValue ?? 0);

        return [
            'total_products_value'    => round($totalValue, 2),
            'converted_products_value'=> round((float) ($productValues->converted_products_value ?? 0), 2),
            'received_value'          => round($received, 2),
            'pending_value'           => round($totalValue - $received, 2),
        ];
    }

    /**
     * 3. Pipeline Funnel.
     */
    public function getPipelineFunnel(array $filters): array
    {
        $base = $this->baseQuery($filters);

        $rows = (clone $base)
            ->selectRaw("
                COALESCE(products.product_name, lead_products.product_name) as product_name,
                SUM(lead_products.total_price) as total_cost,
                COALESCE(SUM(pay.received), 0) as received_amount
            ")
            ->leftJoinSub(
                LeadProductPayment::selectRaw('lead_product_id, SUM(amount) as received')
                    ->groupBy('lead_product_id'),
                'pay',
                'pay.lead_product_id',
                '=',
                'lead_products.id'
            )
            ->groupBy('lead_products.product_id', 'products.product_name', 'lead_products.product_name')
            ->get();

        return $rows->map(function ($row) {
            $total    = (float) $row->total_cost;
            $received = (float) $row->received_amount;
            return [
                'product_name'    => $row->product_name,
                'total_cost'      => round($total, 2),
                'received_amount' => round($received, 2),
                'pending_amount'  => round($total - $received, 2),
            ];
        })->values()->toArray();
    }

    /**
     * 4. Last 6 Months Trend.
     */
    public function getSixMonthTrend(array $filters): array
    {
        $sixMonthsAgo = Carbon::now()->subMonths(5)->startOfMonth();

        $filtersForTrend = $filters;
        // Override date range to last 6 months unless explicitly set
        if (empty($filtersForTrend['from_date'])) {
            $filtersForTrend['from_date'] = $sixMonthsAgo->toDateString();
        }

        $rows = $this->paymentBaseQuery($filtersForTrend)
            ->selectRaw("DATE_FORMAT(lead_product_payments.payment_date, '%Y-%m') as month, SUM(lead_product_payments.amount) as total_value")
            ->where('lead_product_payments.payment_date', '>=', $sixMonthsAgo)
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Fill in missing months
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $months[Carbon::now()->subMonths($i)->format('Y-m')] = 0;
        }

        foreach ($rows as $row) {
            $months[$row->month] = round((float) $row->total_value, 2);
        }

        return collect($months)->map(function ($value, $month) {
            return ['month' => $month, 'total_value' => $value];
        })->values()->toArray();
    }

    /**
     * 5. Top Performing User.
     */
    public function getTopUser(array $filters): array
    {
        $row = $this->paymentBaseQuery($filters)
            ->selectRaw("
                users.id as user_id,
                users.name as user_name,
                users.photo as user_photo,
                branches.name as branch_name,
                users.designation,
                SUM(lead_product_payments.amount) as total_collected_amount
            ")
            ->groupBy('users.id', 'users.name', 'users.photo', 'branches.name', 'users.designation')
            ->orderByDesc('total_collected_amount')
            ->first();

        if (!$row) {
            return [];
        }

        return [
            'user_name'              => $row->user_name,
            'user_photo'             => $row->user_photo ? asset('storage/' . $row->user_photo) : null,
            'branch_name'            => $row->branch_name,
            'designation'            => $row->designation,
            'total_collected_amount' => round((float) $row->total_collected_amount, 2),
        ];
    }

    /**
     * 6. Branch-wise Payments (Last 6 Months).
     */
    public function getBranchPayments(array $filters): array
    {
        $sixMonthsAgo = Carbon::now()->subMonths(5)->startOfMonth();

        $rows = $this->paymentBaseQuery($filters)
            ->selectRaw("
                branches.name as branch_name,
                DATE_FORMAT(lead_product_payments.payment_date, '%Y-%m') as month,
                SUM(lead_product_payments.amount) as total_payment
            ")
            ->where('lead_product_payments.payment_date', '>=', $sixMonthsAgo)
            ->groupBy('branches.id', 'branches.name', 'month')
            ->orderBy('branches.name')
            ->orderBy('month')
            ->get();

        return $rows->map(function ($row) {
            return [
                'branch_name'   => $row->branch_name ?? 'Unknown',
                'month'         => $row->month,
                'total_payment' => round((float) $row->total_payment, 2),
            ];
        })->values()->toArray();
    }

    /**
     * 7. Product-wise Sales (Last 6 Months).
     */
    public function getProductSales(array $filters): array
    {
        $sixMonthsAgo = Carbon::now()->subMonths(5)->startOfMonth();

        $rows = $this->baseQuery($filters)
            ->selectRaw("
                COALESCE(products.product_name, lead_products.product_name) as product_name,
                SUM(lead_products.total_price) as total_sales
            ")
            ->where('lead_products.created_at', '>=', $sixMonthsAgo)
            ->groupBy('lead_products.product_id', 'products.product_name', 'lead_products.product_name')
            ->orderByDesc('total_sales')
            ->get();

        return $rows->map(function ($row) {
            return [
                'product_name' => $row->product_name,
                'total_sales'  => round((float) $row->total_sales, 2),
            ];
        })->values()->toArray();
    }

    /**
     * 8. User-wise Collection (Last 6 Months).
     */
    public function getUserCollections(array $filters): array
    {
        $sixMonthsAgo = Carbon::now()->subMonths(5)->startOfMonth();

        $rows = $this->paymentBaseQuery($filters)
            ->selectRaw("
                users.name as user_name,
                SUM(lead_product_payments.amount) as total_collection
            ")
            ->where('lead_product_payments.payment_date', '>=', $sixMonthsAgo)
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_collection')
            ->get();

        return $rows->map(function ($row) {
            return [
                'user_name'        => $row->user_name,
                'total_collection' => round((float) $row->total_collection, 2),
            ];
        })->values()->toArray();
    }

    /**
     * Compile all dashboard data.
     */
    public function getDashboardData(array $filters): array
    {
        return [
            'product_summary'  => $this->getProductSummary($filters),
            'value_summary'    => $this->getValueSummary($filters),
            'pipeline_funnel'  => $this->getPipelineFunnel($filters),
            'six_month_trend'  => $this->getSixMonthTrend($filters),
            'top_user'         => $this->getTopUser($filters),
            'branch_payments'  => $this->getBranchPayments($filters),
            'product_sales'    => $this->getProductSales($filters),
            'user_collections' => $this->getUserCollections($filters),
        ];
    }
}
