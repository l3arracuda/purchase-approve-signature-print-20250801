<?php
// app/Providers/AppServiceProvider.php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\PoApprovedService;
use App\Services\PurchaseOrderService;
use App\Services\NotificationService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // ลงทะเบียน Services
        $this->app->singleton(PoApprovedService::class, function ($app) {
            return new PoApprovedService();
        });

        $this->app->singleton(PurchaseOrderService::class, function ($app) {
            return new PurchaseOrderService();
        });

        $this->app->singleton(NotificationService::class, function ($app) {
            return new NotificationService();
        });

        // Development helpers
        if ($this->app->environment('local')) {
            // เพิ่ม Development tools ถ้าต้องการ
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Database connection สำหรับ multiple databases
        $this->configureDatabaseConnections();

        // Model observers (ถ้าต้องการ)
        $this->registerModelObservers();

        // Custom validation rules (ถ้าต้องการ)
        $this->registerCustomValidationRules();
    }

    /**
     * Configure database connections
     */
    private function configureDatabaseConnections()
    {
        // ตรวจสอบการเชื่อมต่อฐานข้อมูลเมื่อ boot
        try {
            // ทดสอบ Modern Database
            \DB::connection('modern')->getPdo();
            
            // ทดสอบ Legacy Database
            \DB::connection('legacy')->getPdo();
            
        } catch (\Exception $e) {
            // Log error แต่ไม่ให้ app crash
            \Log::error('Database connection issue during boot: ' . $e->getMessage());
        }
    }

    /**
     * Register model observers
     */
    private function registerModelObservers()
    {
        // เพิ่ม Model Observers ตรงนี้ถ้าต้องการ
        // User::observe(UserObserver::class);
    }

    /**
     * Register custom validation rules
     */
    private function registerCustomValidationRules()
    {
        // เพิ่ม Custom Validation Rules ถ้าต้องการ
        \Validator::extend('po_number', function ($attribute, $value, $parameters, $validator) {
            return preg_match('/^PP\d+$/', $value);
        });

        \Validator::extend('approval_level', function ($attribute, $value, $parameters, $validator) {
            return in_array($value, [1, 2, 3, 99]);
        });
    }
}