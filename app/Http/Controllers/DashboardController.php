<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $metrics = null;

        if ($request->user()->can(Permission::ViewMetrics->value)) {
            $metrics = [
                'users_total' => User::query()->count(),
                'users_active' => User::query()->where('is_active', true)->count(),
            ];
        }

        return view('dashboard', ['metrics' => $metrics]);
    }
}
