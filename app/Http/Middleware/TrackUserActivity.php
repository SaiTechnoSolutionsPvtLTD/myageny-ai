<?php

namespace App\Http\Middleware;

use App\Models\Lead;
use App\Services\ActivityLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class TrackUserActivity
{
    /**
     * Paths to completely ignore from activity logs.
     */
    protected array $ignoredPaths = [
        'up',
        '_debugbar*',
        'sanctum/*',
        'api/notifications*',
        'api/unread*',
        'settings/activity-logs*',
        '*.js',
        '*.css',
        '*.png',
        '*.jpg',
        '*.jpeg',
        '*.svg',
        '*.woff*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only log for authenticated users
        if (! Auth::check()) {
            return $response;
        }

        // Only log if response was successful or redirect (status < 400)
        if ($response->getStatusCode() >= 400) {
            return $response;
        }

        $this->processLogging($request, $response);

        return $response;
    }

    protected function processLogging(Request $request, Response $response): void
    {
        $path = $request->path();

        // Check ignored paths
        foreach ($this->ignoredPaths as $pattern) {
            if (Str::is($pattern, $path)) {
                return;
            }
        }

        $method = strtoupper($request->method());

        // We log all state-changing methods (POST, PUT, PATCH, DELETE) and significant GET page requests
        $isStateChanging = in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE']);
        $isGetPage = $method === 'GET' && ! $request->ajax() && ! $request->wantsJson();

        if (! $isStateChanging && ! $isGetPage) {
            return;
        }

        $user = Auth::user();
        $module = $this->resolveModule($request);
        $action = $this->resolveAction($request, $method);
        $leadId = $this->resolveLeadId($request);
        $description = $this->generateDescription($request, $module, $action, $method, $user->name);

        // Filter out sensitive fields from request inputs
        $payload = $request->except([
            '_token',
            '_method',
            'password',
            'password_confirmation',
            'current_password',
            'new_password',
        ]);

        $properties = [];
        if (! empty($payload) && $isStateChanging) {
            $properties['input'] = array_slice($payload, 0, 30); // limit payload keys
        }

        ActivityLogger::log(
            module: $module,
            action: $action,
            description: $description,
            leadId: $leadId,
            properties: $properties,
            user: $user,
            request: $request
        );
    }

    protected function resolveModule(Request $request): string
    {
        $routeName = $request->route()?->getName() ?: '';
        $path = strtolower($request->path());

        if (Str::startsWith($routeName, 'leads.') || Str::contains($path, 'lead')) {
            return 'Leads';
        }
        if (Str::startsWith($routeName, 'quotation.') || Str::contains($path, 'quotation')) {
            return 'Quotations';
        }
        if (Str::startsWith($routeName, 'production-') || Str::contains($path, 'production') || Str::contains($path, 'ovp')) {
            return 'Production';
        }
        if (Str::startsWith($routeName, 'projects.') || Str::contains($path, 'project')) {
            return 'Projects';
        }
        if (Str::startsWith($routeName, 'products.') || Str::contains($path, 'product')) {
            return 'Products';
        }
        if (Str::startsWith($routeName, 'hrms.') || Str::contains($path, 'hrms') || Str::contains($path, 'payroll') || Str::contains($path, 'attendance') || Str::contains($path, 'leave')) {
            return 'HRMS';
        }
        if (Str::startsWith($routeName, 'settings.') || Str::contains($path, 'settings')) {
            return 'Settings';
        }
        if (Str::startsWith($routeName, 'reports.') || Str::contains($path, 'report')) {
            return 'Reports';
        }
        if (Str::contains($path, 'dashboard')) {
            return 'Dashboard';
        }

        return 'General';
    }

    protected function resolveAction(Request $request, string $method): string
    {
        $routeName = $request->route()?->getName() ?: '';

        if (Str::endsWith($routeName, '.store') || $method === 'POST') {
            if (Str::contains($routeName, 'status')) return 'status_change';
            if (Str::contains($routeName, 'allocate')) return 'allocated';
            if (Str::contains($routeName, 'approve') || Str::contains($routeName, 'review')) return 'approved';
            if (Str::contains($routeName, 'reject')) return 'rejected';
            if (Str::contains($routeName, 'payment')) return 'payment_added';
            return 'created';
        }

        if (Str::endsWith($routeName, '.update') || in_array($method, ['PUT', 'PATCH'])) {
            if (Str::contains($routeName, 'status')) return 'status_change';
            return 'updated';
        }

        if (Str::endsWith($routeName, '.destroy') || $method === 'DELETE') {
            return 'deleted';
        }

        if ($method === 'GET') {
            if (Str::contains($routeName, 'export')) return 'exported';
            if (Str::endsWith($routeName, '.show')) return 'viewed_details';
            return 'viewed';
        }

        return strtolower($method);
    }

    protected function resolveLeadId(Request $request): ?int
    {
        // 1. From route param {lead}
        $routeLead = $request->route('lead');
        if ($routeLead instanceof Lead) {
            return $routeLead->id;
        }
        if (is_numeric($routeLead)) {
            return (int) $routeLead;
        }

        // 2. From route param {lead_id} or request inputs
        $inputLeadId = $request->route('lead_id') ?: $request->input('lead_id');
        if (is_numeric($inputLeadId)) {
            return (int) $inputLeadId;
        }

        // 3. From URL regex /leads/(\d+)
        if (preg_match('#leads/(\d+)#i', $request->path(), $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    protected function generateDescription(Request $request, string $module, string $action, string $method, string $userName): string
    {
        $leadId = $this->resolveLeadId($request);
        $leadSuffix = $leadId ? " for Lead #{$leadId}" : "";

        return match ($action) {
            'created' => "{$userName} created new record in {$module}{$leadSuffix}",
            'updated' => "{$userName} updated record in {$module}{$leadSuffix}",
            'deleted' => "{$userName} deleted record in {$module}{$leadSuffix}",
            'status_change' => "{$userName} changed status in {$module}{$leadSuffix}",
            'approved' => "{$userName} approved request in {$module}{$leadSuffix}",
            'rejected' => "{$userName} rejected request in {$module}{$leadSuffix}",
            'allocated' => "{$userName} made allocation in {$module}{$leadSuffix}",
            'payment_added' => "{$userName} recorded payment{$leadSuffix}",
            'exported' => "{$userName} exported {$module} data",
            'viewed_details' => "{$userName} viewed details page in {$module}{$leadSuffix}",
            'viewed' => "{$userName} visited {$module} page",
            default => "{$userName} performed {$action} in {$module}{$leadSuffix}",
        };
    }
}
