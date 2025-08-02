<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\SignatureController;

Route::get('/', function () {
    return redirect('/login');
});

Auth::routes(['register' => false]); // ปิดการ register

Route::get('/home', [DashboardController::class, 'index'])->name('home');

// PO Management Routes
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Purchase Orders
    Route::get('/po', [PurchaseOrderController::class, 'index'])->name('po.index');
    Route::get('/po/{docNo}', [PurchaseOrderController::class, 'show'])->name('po.show');
    Route::post('/po/{docNo}/approve', [PurchaseOrderController::class, 'approve'])->name('po.approve');
    Route::post('/po/bulk-approve', [PurchaseOrderController::class, 'bulkApprove'])->name('po.bulk-approve');
    Route::post('/po/bulk-action', [PurchaseOrderController::class, 'bulkAction'])->name('po.bulk-action');
    
    // ========== PO Approved Routes ==========
    Route::get('/po-approved', [PurchaseOrderController::class, 'approved'])->name('po.approved');
    
    // ========== API Routes for PO Approved ==========
    Route::prefix('api/po-approved')->group(function () {
        Route::get('/list', [App\Http\Controllers\Api\PoApprovedController::class, 'getApprovedPOs'])->name('api.po-approved.list');
        Route::get('/stats', [App\Http\Controllers\Api\PoApprovedController::class, 'getStats'])->name('api.po-approved.stats');
        Route::get('/export/csv', [App\Http\Controllers\Api\PoApprovedController::class, 'exportCSV'])->name('api.po-approved.export-csv');
        Route::get('/export/excel', [App\Http\Controllers\Api\PoApprovedController::class, 'exportExcel'])->name('api.po-approved.export-excel');
        Route::get('/data-status', [App\Http\Controllers\Api\PoApprovedController::class, 'getDataStatus'])->name('api.po-approved.data-status');
        Route::get('/detail/{docNo}', [App\Http\Controllers\Api\PoApprovedController::class, 'getPODetail'])->name('api.po-approved.detail');
        Route::post('/update-customer-data', [App\Http\Controllers\Api\PoApprovedController::class, 'updateCustomerData'])->name('api.po-approved.update-customer-data');
    });
    
    // ========== Digital Signature Routes ==========
    // Signature Management Pages
    Route::get('/signature/manage', [SignatureController::class, 'manage'])->name('signature.manage');
    
    // Signature Operations
    Route::post('/signature/upload', [SignatureController::class, 'upload'])->name('signature.upload');
    Route::post('/signature/{id}/activate', [SignatureController::class, 'activate'])->name('signature.activate');
    Route::post('/signature/{id}/deactivate', [SignatureController::class, 'deactivate'])->name('signature.deactivate');
    Route::delete('/signature/{id}', [SignatureController::class, 'delete'])->name('signature.delete');
    
    // Signature API Routes (for AJAX calls)
    Route::get('/api/signature/active', [SignatureController::class, 'getActiveSignature'])->name('api.signature.active');
    Route::get('/api/signature/check', [SignatureController::class, 'hasActiveSignature'])->name('api.signature.check');
    
    // ========== HTML Print Routes ==========
    // Print PO (HTML page)
    Route::get('/po/{docNo}/print', [PurchaseOrderController::class, 'printPO'])->name('po.print');
    
    // Print Popup (for popup window)
    Route::get('/po/{docNo}/print/popup', [PurchaseOrderController::class, 'printPopup'])->name('po.print.popup');
    
    // Print Status Check (AJAX)
    Route::get('/po/{docNo}/print/status', [PurchaseOrderController::class, 'checkPrintStatus'])->name('po.print.status');
    
    // Print History
    Route::get('/po/{docNo}/print-history', [PurchaseOrderController::class, 'printHistory'])->name('po.print.history');
    
    // Debug Print Data (Admin only)
    Route::get('/po/{docNo}/print/debug', [PurchaseOrderController::class, 'debugPrint'])->name('po.print.debug');
    
    // Export Print Data (Manager+ only)
    Route::get('/po/{docNo}/print/export', [PurchaseOrderController::class, 'exportPrintData'])->name('po.print.export');
});

// ========== FUTURE: Admin Routes ==========
/*
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('admin.users');
    Route::get('/settings', [SettingsController::class, 'index'])->name('admin.settings');
    Route::get('/reports', [ReportController::class, 'index'])->name('admin.reports');
});
*/

// เพิ่ม routes ทดสอบ
// require __DIR__.'/test.php';