<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

$user = DB::table('users as u')
    ->leftJoin('employee_onboardings as eo', function ($join) {
        $join->on('eo.portal_user_id', '=', 'u.id')
             ->whereNull('eo.deleted_at');
    })
    ->leftJoin('departments as dep', 'dep.id', '=', 'eo.department_id')
    ->where('u.id', 6)
    ->select('u.id', 'u.name', 'eo.department_id', 'dep.name as dept_name')
    ->first();

print_r($user);
