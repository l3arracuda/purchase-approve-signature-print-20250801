<?php
// app/Providers/AppServiceProvider.php (อัปเดต)

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\PurchaseOrderService;
use App\Services\PrintService;
use App\Services\NotificationService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register PurchaseOrderService
        $this->app->bind(PurchaseOrderService::class, function ($app) {
            return new PurchaseOrderService();
        });

        // Register PrintService (แทน PDFService)
        $this->app->bind(PrintService::class, function ($app) {
            return new PrintService($app->make(PurchaseOrderService::class));
        });

        // Register NotificationService
        $this->app->bind(NotificationService::class, function ($app) {
            return new NotificationService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}