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

        $settings = QuotationSetting::where('branch_id', $branchId)->get();;

        $data = [
            'logo' => $settings->where('key', 'logo')->first()?->value,
            'theme_color' => $settings->where('key', 'theme_color')->first()?->value,
            'secondary_color' => $settings->where('key', 'secondary_color')->first()?->value,
            'header_text_color' => $settings->where('key', 'header_text_color')->first()?->value,
            'prefix' => $settings->where('key', 'prefix')->first()?->value,
            'number_padding' => $settings->where('key', 'number_padding')->first()?->value,
            'terms' => $settings->where('key', 'terms')->first()?->value,
            'company_address' => $settings->where('key', 'company_address')->first()?->value,
            'company_name' => $settings->where('key', 'company_name')->first()?->value,
            'company_phone' => $settings->where('key', 'company_phone')->first()?->value,
            'company_email' => $settings->where('key', 'company_email')->first()?->value,
            'company_gstin' => $settings->where('key', 'company_gstin')->first()?->value,
            'bank_name' => $settings->where('key', 'bank_name')->first()?->value,
            'bank_account' => $settings->where('key', 'bank_account')->first()?->value,
            'bank_ifsc' => $settings->where('key', 'bank_ifsc')->first()?->value,
            'watermark_text' => $settings->where('key', 'watermark_text')->first()?->value,
            'signature' => $settings->where('key', 'signature')->first()?->value,
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
            'account_name'     => ['nullable', 'string', ],
            'bank_ifsc'        => ['nullable', 'string', 'max:15'],
            'bank_branch'        => ['nullable', 'string', ],
            'bank_upi'        => ['nullable', 'string',],
            'watermark_text'   => ['nullable', 'string', 'max:50'],
            'show_watermark'   => ['nullable', 'boolean'],
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
        foreach ($textSettings as $key => $value) {
            if ($value !== null) {
                QuotationSetting::set($key, $value, $branchId);
            }
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
