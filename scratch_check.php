<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== ROLES ===" . PHP_EOL;
foreach (\App\Models\Role::withoutGlobalScopes()->get(['id', 'name', 'display_name', 'company_id']) as $r) {
    echo "[{$r->id}] {$r->name} | {$r->display_name} | company: {$r->company_id}" . PHP_EOL;
}

echo PHP_EOL . "=== EXPENSE PIPELINES ===" . PHP_EOL;
foreach (\App\Models\ExpensePipeline::withoutGlobalScopes()->get() as $p) {
    echo "[{$p->id}] role_id: {$p->role_id}, comp: {$p->company_id}, chain: " . json_encode($p->approval_chain) . ", active: " . ($p->is_active ? 'yes' : 'no') . PHP_EOL;
}

echo PHP_EOL . "=== LATEST EXPENSE REQUESTS ===" . PHP_EOL;
foreach (\App\Models\ExpenseRequest::withoutGlobalScopes()->latest()->take(5)->get() as $req) {
    $applicant = \App\Models\User::withoutGlobalScopes()->find($req->user_id);
    $applicantRoles = $applicant ? $applicant->roles->pluck('id', 'name')->toArray() : [];
    echo "[REQ #{$req->id}] User: {$req->user_id} ({$applicant?->name}), current_step: {$req->current_step}, current_approver_role_id: {$req->current_approver_role_id}, status: {$req->status}" . PHP_EOL;
    echo "  Applicant roles: " . json_encode($applicantRoles) . PHP_EOL;
}

echo PHP_EOL . "=== USERS WITH COORDINATOR / DEVELOPER / HR ROLES ===" . PHP_EOL;
foreach (\App\Models\User::withoutGlobalScopes()->with('roles')->get() as $u) {
    $rNames = $u->roles->pluck('name')->implode(', ');
    if (stripos($rNames, 'coordinator') !== false || stripos($rNames, 'develop') !== false || stripos($rNames, 'hr') !== false) {
        echo "[User #{$u->id}] {$u->name} ({$u->email}) - Roles: [{$rNames}] - Company: {$u->company_id}" . PHP_EOL;
    }
}
