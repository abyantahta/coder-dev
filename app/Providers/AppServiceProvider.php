<?php

namespace App\Providers;

use App\Models\Department;
use App\Models\WoCategory;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.app', function ($view) {
            $user = Auth::user();
            $view->with('woInboxCount', $user
                ? WorkOrder::visibleTo($user)->waitingOn($user)->count()
                : 0);

            // "WO Saya Kirim" badge: my own WOs that are done and wait for my review.
            $view->with('woRequestedReviewCount', $user
                ? WorkOrder::where('requester_id', $user->id)->where('status', 'completed')->count()
                : 0);

            if (! $user) {
                $view->with('woCreateDepartments', collect());
                $view->with('woCreateCategoriesByDept', collect());
                return;
            }

            $view->with('woCreateDepartments', Department::where('is_active', true)->orderBy('name')->get());
            $view->with('woCreateCategoriesByDept', WoCategory::where('is_active', true)
                ->orderBy('sort_order')
                ->get(['id', 'name', 'department_id'])
                ->groupBy('department_id')
                ->map(fn ($cats) => $cats->map(fn ($c) => [
                    'id'   => $c->id,
                    'name' => $c->name,
                ])->values()));
        });
    }
}
