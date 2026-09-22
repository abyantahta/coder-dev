<?php

namespace App\Providers;

use App\Models\DeliveryNote;
use App\Models\DeliverySchedule;
use App\Models\Forecast;
use App\Models\PurchaseOrder;
use App\Policies\DeliveryNotePolicy;
use App\Policies\DeliverySchedulePolicy;
use App\Policies\ForecastPolicy;
use App\Policies\PurchaseOrderPolicy;
use App\Services\Qad\QadClientInterface;
use App\Services\Qad\QadReceivingClient;
use App\Services\Qad\QadSoapClient;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(QadClientInterface::class, QadReceivingClient::class);
        $this->app->bind(QadSoapClient::class, fn () => QadSoapClient::fromConfig());
    }

    public function boot(): void
    {
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }
        // Vite::prefetch(concurrency: 3);

        Gate::policy(PurchaseOrder::class, PurchaseOrderPolicy::class);
        Gate::policy(DeliveryNote::class, DeliveryNotePolicy::class);
        Gate::policy(DeliverySchedule::class, DeliverySchedulePolicy::class);
        Gate::policy(Forecast::class, ForecastPolicy::class);
    }
}
