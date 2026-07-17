<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Services\MenuService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppMenuController extends Controller
{
    public function __construct(private readonly MenuService $menuService) {}

    /** GET /mobile/menu?module=crm|projects|hrms (defaults to crm) */
    public function index(Request $request): JsonResponse
    {
        $module = $request->query('module', 'crm');

        return response()->json([
            'status' => true,
            'module' => $module,
            'data'   => $this->menuService->build($request->user(), $module),
        ]);
    }

    /** GET /mobile/modules — feeds the module-switcher FAB */
    public function modules(Request $request): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data'   => $this->menuService->accessibleModules($request->user()),
        ]);
    }
}
