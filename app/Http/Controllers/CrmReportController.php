<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class CrmReportController extends Controller
{
    public function index(): View
    {
        $reports = [
            [
                'title' => 'Leads Summary',
                'description' => 'Track monthly lead volume, ownership, and follow-up activity from one report workspace.',
                'theme' => 'lead',
                'status' => 'Ready for setup',
            ],
            [
                'title' => 'Lead Products',
                'description' => 'Review product-wise enquiries, deal movement, and current opportunity distribution.',
                'theme' => 'product',
                'status' => 'Ready for setup',
            ],
            [
                'title' => 'Sales Pipeline',
                'description' => 'Monitor stage-wise conversion flow, bottlenecks, and expected closures across teams.',
                'theme' => 'pipeline',
                'status' => 'Coming soon',
            ],
            [
                'title' => 'Campaign Performance',
                'description' => 'Compare source channels and campaign intake quality to spot the strongest lead generators.',
                'theme' => 'campaign',
                'status' => 'Coming soon',
            ],
            [
                'title' => 'Team Follow-up',
                'description' => 'Understand pending calls, response speed, and ownership load for each CRM user.',
                'theme' => 'team',
                'status' => 'Coming soon',
            ],
            [
                'title' => 'Quotation Insights',
                'description' => 'Measure quotation creation, customer response, and conversion trend from proposal to close.',
                'theme' => 'quotation',
                'status' => 'Coming soon',
            ],
        ];

        return view('pages.reports.crm.index', compact('reports'));
    }
}
