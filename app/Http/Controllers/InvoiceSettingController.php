<?php

namespace App\Http\Controllers;

use App\Models\InvoiceSetting;
use Illuminate\Http\Request;

class InvoiceSettingController extends Controller
{
    /**
     * Display the Invoice Settings management page.
     */
    public function index()
    {
        $companyId = auth()->user()?->company_id;
        $setting = InvoiceSetting::forCompany($companyId);

        return view('pages.accounts.master.invoice_settings', compact('setting'));
    }

    /**
     * Update Invoice Settings.
     */
    public function update(Request $request)
    {
        $companyId = auth()->user()?->company_id;

        $validated = $request->validate([
            // Numbering
            'invoice_prefix'   => 'nullable|string|max:50',
            'date_format'      => 'required|string|in:financial_year,calendar_year,year_month,none',
            'next_number'      => 'required|integer|min:1',
            'number_padding'   => 'required|integer|min:2|max:10',
            'invoice_suffix'   => 'nullable|string|max:50',
            'reset_frequency'  => 'required|string|in:financial_year,yearly,monthly,never',

            // Company Profile
            'company_name'     => 'nullable|string|max:255',
            'company_address'  => 'nullable|string',
            'company_city'     => 'nullable|string|max:100',
            'company_state'    => 'nullable|string|max:100',
            'company_pincode'  => 'nullable|string|max:20',
            'company_phone'    => 'nullable|string|max:50',
            'company_email'    => 'nullable|email|max:255',
            'company_gstin'    => 'nullable|string|max:50',
            'company_pan'      => 'nullable|string|max:30',

            // Bank Details
            'bank_name'        => 'nullable|string|max:150',
            'account_name'     => 'nullable|string|max:150',
            'account_number'   => 'nullable|string|max:60',
            'ifsc_code'        => 'nullable|string|max:30',
            'branch'           => 'nullable|string|max:150',
            'upi_id'           => 'nullable|string|max:100',
            'swift_code'       => 'nullable|string|max:50',

            // Terms & Notes
            'terms_conditions' => 'nullable|string',
            'notes'            => 'nullable|string',

            // Signature
            'signatory_name'   => 'nullable|string|max:150',

            // Files
            'logo'             => 'nullable|image|mimes:jpeg,png,jpg,svg,webp|max:3072',
            'signature'        => 'nullable|image|mimes:jpeg,png,jpg,svg,webp|max:2048',
        ]);

        $setting = InvoiceSetting::firstOrNew(['company_id' => $companyId]);

        // Handle Logo Upload
        if ($request->hasFile('logo')) {
            if ($setting->logo_path && file_exists(public_path($setting->logo_path))) {
                @unlink(public_path($setting->logo_path));
            }

            $logoDir = public_path('uploads/invoice/logo');
            if (!file_exists($logoDir)) {
                mkdir($logoDir, 0755, true);
            }

            $logoFile = time() . '_logo_' . uniqid() . '.' . $request->file('logo')->getClientOriginalExtension();
            $request->file('logo')->move($logoDir, $logoFile);
            $setting->logo_path = 'uploads/invoice/logo/' . $logoFile;
        }

        // Handle Signature Upload
        if ($request->hasFile('signature')) {
            if ($setting->signature_path && file_exists(public_path($setting->signature_path))) {
                @unlink(public_path($setting->signature_path));
            }

            $sigDir = public_path('uploads/invoice/signature');
            if (!file_exists($sigDir)) {
                mkdir($sigDir, 0755, true);
            }

            $sigFile = time() . '_sig_' . uniqid() . '.' . $request->file('signature')->getClientOriginalExtension();
            $request->file('signature')->move($sigDir, $sigFile);
            $setting->signature_path = 'uploads/invoice/signature/' . $sigFile;
        }

        // Fill non-file attributes
        $fillData = collect($validated)->except(['logo', 'signature'])->toArray();
        $setting->fill($fillData);

        $setting->company_id = $companyId;
        $setting->updated_by = auth()->id();
        if (!$setting->exists) {
            $setting->created_by = auth()->id();
        }

        $setting->save();

        return redirect()->back()->with('success', 'Invoice settings updated successfully!');
    }

    /**
     * Remove Logo or Signature file.
     */
    public function removeFile(Request $request, string $type)
    {
        $companyId = auth()->user()?->company_id;
        $setting = InvoiceSetting::where('company_id', $companyId)->first();

        if (!$setting) {
            return redirect()->back()->with('error', 'Settings record not found.');
        }

        if ($type === 'logo') {
            if ($setting->logo_path && file_exists(public_path($setting->logo_path))) {
                @unlink(public_path($setting->logo_path));
            }
            $setting->logo_path = null;
            $setting->save();
            return redirect()->back()->with('success', 'Company logo removed successfully.');
        }

        if ($type === 'signature') {
            if ($setting->signature_path && file_exists(public_path($setting->signature_path))) {
                @unlink(public_path($setting->signature_path));
            }
            $setting->signature_path = null;
            $setting->save();
            return redirect()->back()->with('success', 'Authorized signature removed successfully.');
        }

        return redirect()->back()->with('error', 'Invalid file type.');
    }
}
