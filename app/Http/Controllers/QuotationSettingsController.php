<?php

// ============================================================
// FILE: app/Http/Controllers/Quotation/QuotationSettingsController.php
// ============================================================
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\QuotationSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class QuotationSettingsController extends Controller
{
    /**
     * Display the quotation settings page.
     */
    public function index()
    {

        $branchId = auth()->user()->branch_id;

        $settings = QuotationSetting::where('branch_id', $branchId)->get();
        $globalSettings = QuotationSetting::whereNull('branch_id')->get();
        $val = fn($k) => $settings->where('key', $k)->first()?->value ?? $globalSettings->where('key', $k)->first()?->value;

        $data = [
            'logo' => $val('logo'),
            'theme_color' => $val('theme_color'),
            'secondary_color' => $val('secondary_color'),
            'header_text_color' => $val('header_text_color'),
            'prefix' => $val('prefix'),
            'number_padding' => $val('number_padding'),
            'terms' => $val('terms'),
            'company_address' => $val('company_address'),
            'company_name' => $val('company_name'),
            'company_phone' => $val('company_phone'),
            'company_email' => $val('company_email'),
            'company_gstin' => $val('company_gstin'),
            'bank_name' => $val('bank_name'),
            'account_name' => $val('account_name'),
            'bank_account' => $val('bank_account'),
            'bank_ifsc' => $val('bank_ifsc'),
            'bank_branch' => $val('bank_branch'),
            'bank_upi' => $val('bank_upi'),
            'watermark_text' => $val('watermark_text'),
            'show_watermark' => (bool) $val('show_watermark'),
            'signature' => $val('signature'),
        ];


        return view('pages.settings.quotation-setting.index', compact('settings', 'branchId', 'data'));
    }

    /**
     * Save all quotation settings.
     */
    public function update(Request $request)
    {

        $branchId = auth()->user()->branch_id;

        $validated = $request->validate([
            'theme_color'      => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color'  => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'header_text_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'prefix'           => ['nullable', 'string', 'max:10', 'alpha_dash'],
            'number_padding'   => ['nullable', 'integer', 'min:3', 'max:10'],
            'terms'            => ['nullable', 'string'],
            'company_address'  => ['nullable', 'string', 'max:500'],
            'company_name'     => ['nullable', 'string', 'max:150'],
            'company_phone'    => ['nullable', 'string', 'max:30'],
            'company_email'    => ['nullable', 'email'],
            'company_gstin'    => ['nullable', 'string', 'max:20'],
            'bank_name'        => ['nullable', 'string', 'max:100'],
            'bank_account'     => ['nullable', 'string', 'max:30'],
            'account_name'     => ['nullable', 'string', 'max:150'],
            'bank_ifsc'        => ['nullable', 'string', 'max:15'],
            'bank_branch'      => ['nullable', 'string', 'max:150'],
            'bank_upi'         => ['nullable', 'string', 'max:100'],
            'watermark_text'   => ['nullable', 'string', 'max:50'],
            'show_watermark'   => ['nullable'],
            'logo'             => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg', 'max:2048'],
            'signature'        => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:1024'],
        ]);

        // ─── Handle file uploads ──────────────────────────────
        foreach (['logo', 'signature'] as $fileField) {

    if ($request->hasFile($fileField)) {

        // Delete old file
        $oldPath = QuotationSetting::get(
            $fileField,
            $branchId
        );

        if ($oldPath && file_exists(public_path($oldPath))) {
            unlink(public_path($oldPath));
        }

        $file = $request->file($fileField);

        $filename = time().'_'.$file->getClientOriginalName();

        $file->move(
            public_path("uploads/quotation/{$fileField}"),
            $filename
        );

        $path = "uploads/quotation/{$fileField}/".$filename;

        QuotationSetting::set(
            $fileField,
            $path,
            $branchId
        );
    }
}

        // ─── Save text/color settings ─────────────────────────
        $textSettings = array_diff_key($validated, array_flip(['logo', 'signature']));
        $textSettings['show_watermark'] = $request->boolean('show_watermark') ? '1' : '0';

        foreach ($textSettings as $key => $value) {
            QuotationSetting::set($key, $value ?? '', $branchId);
        }

        return back()->with('success', 'Quotation settings saved successfully.');
    }

    /**
     * Delete a specific file (logo or signature).
     */
    public function deleteFile(Request $request, string $type)
    {

        abort_unless(in_array($type, ['logo', 'signature']), 404);

        $branchId = auth()->user()->branch_id;
        $path = QuotationSetting::get($type, $branchId);

        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        QuotationSetting::set($type, null, $branchId);

        return response()->json(['success' => true]);
    }


    // ─── API endpoint for mobile ──────────────────────────────
    public function apiIndex()
    {
        $settings = QuotationSetting::allSettings(auth()->user()->branch_id);

        return response()->json(['data' => $settings]);
    }

    public function facebookIntegration()
    {
        return view('pages.settings.facebook-integration.index');
    }
}
