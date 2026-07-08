<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LeadReallocationController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->where('user_status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('pages.settings.lead-reallocation.index', compact('users'));
    }

    public function reallocate(Request $request)
    {
        $request->validate([
            'from_user_id' => 'required|exists:users,id',
            'to_user_id' => 'required|exists:users,id',
        ]);

        $fromUserId = $request->input('from_user_id');
        $toUserId = $request->input('to_user_id');

        if ($fromUserId == $toUserId) {
            return back()->with('error', 'Please select different users for reallocation.');
        }

        // Start transaction
        DB::beginTransaction();

        try {
            // Get count of leads to be reallocated
            $leadsCount = DB::table('leads')
                ->where('assigned_to', $fromUserId)
                ->count();

            // Get count of lead products to be reallocated
            $productsCount = DB::table('lead_products')
                ->join('leads', 'leads.id', '=', 'lead_products.lead_id')
                ->where('leads.assigned_to', $fromUserId)
                ->count();

            // Reallocate leads
            DB::table('leads')
                ->where('assigned_to', $fromUserId)
                ->update(['assigned_to' => $toUserId]);

            // Reallocate lead_products (though they inherit from leads)
            DB::table('lead_products')
                ->join('leads', 'leads.id', '=', 'lead_products.lead_id')
                ->where('leads.assigned_to', $toUserId)
                ->update(['lead_products.updated_at' => now()]);

            DB::commit();

            $fromUser = User::find($fromUserId);
            $toUser = User::find($toUserId);

            return back()->with('success', "Successfully reallocated {$leadsCount} leads and related products from {$fromUser->name} to {$toUser->name}.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'An error occurred during reallocation: ' . $e->getMessage());
        }
    }
}