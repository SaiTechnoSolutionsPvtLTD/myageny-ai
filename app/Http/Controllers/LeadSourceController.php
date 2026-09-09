<?php
// app/Http/Controllers/Settings/LeadSourceController.php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeadSourceRequest;
use App\Models\LeadSource;

class LeadSourceController extends Controller
{
    public function index()
    {
        $sources = LeadSource::latest()
            ->paginate(10)
            ->withQueryString();
        return view('pages.settings.lead-source.index', compact('sources'));
    }

    public function store(LeadSourceRequest $request)
    {
        LeadSource::create([
            'name'       => $request->name,
        ]);

        return redirect()->route('masters.lead-sources.index')
                         ->with('success', 'Lead Source created successfully.');
    }

    public function edit(LeadSource $leadSource)
    {
        $this->authorizeCompany($leadSource);
        return view('settings.lead-source.edit', compact('leadSource'));
    }

    public function update(LeadSourceRequest $request, LeadSource $leadSource)
    {
        $this->authorizeCompany($leadSource);
        $leadSource->update(['name' => $request->name]);

        return redirect()->route('masters.lead-sources.index')
                         ->with('success', 'Lead Source updated successfully.');
    }

    public function destroy(LeadSource $leadSource)
    {
        $this->authorizeCompany($leadSource);
        $leadSource->delete();

        return redirect()->route('masters.lead-sources.index')
                         ->with('success', 'Lead Source deleted.');
    }

    private function authorizeCompany($model)
    {
        if ($model->company_id !== auth()->user()->company_id) {
            abort(403);
        }
    }
}
