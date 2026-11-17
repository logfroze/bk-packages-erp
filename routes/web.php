<?php

use App\Http\Controllers\AiCopilotController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConversionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GateRegisterController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\KhataController;
use App\Http\Controllers\OfflineSyncController;
use App\Http\Controllers\PettyCashController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

// Public Authentication Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');

// Authenticated ERP Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Accessible by all staff (Admin, Accountant, Gate Operator)
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/gate-register', [GateRegisterController::class, 'index'])->name('gate.index');
    Route::post('/gate-register', [GateRegisterController::class, 'store'])->name('gate.store');
    Route::post('/gate-register/{gate}/weigh-exit', [GateRegisterController::class, 'updateWeightAndExit'])->name('gate.weigh');
    Route::get('/gate-register/{gate}/slip', [GateRegisterController::class, 'slip'])->name('gate.slip');

    // Offline PWA Sync API
    Route::get('/offline-sync/ping', [OfflineSyncController::class, 'ping'])->name('offline.ping');
    Route::post('/offline-sync/replay', [OfflineSyncController::class, 'replay'])->name('offline.replay');

    // Google AI Studio Factory Assistant
    Route::get('/ai-copilot/conversations', [AiCopilotController::class, 'conversations'])->name('ai.conversations.index');
    Route::get('/ai-copilot/conversations/{conversation}', [AiCopilotController::class, 'showConversation'])->name('ai.conversations.show');
    Route::delete('/ai-copilot/conversations/{conversation}', [AiCopilotController::class, 'destroyConversation'])->name('ai.conversations.destroy');
    Route::post('/ai-copilot/messages/{message}/dismiss', [AiCopilotController::class, 'dismissAction'])->name('ai.messages.dismiss');
    Route::post('/ai-copilot/chat', [AiCopilotController::class, 'chat'])->name('ai.chat');
    Route::post('/ai-copilot/execute', [AiCopilotController::class, 'execute'])->name('ai.execute');

    // Restricted to Accountant & Admin (Director)
    Route::middleware('role:ADMIN,ACCOUNTANT')->group(function () {
        // Production & Corrugation
        Route::get('/production', [ProductionController::class, 'index'])->name('production.index');
        Route::post('/production', [ProductionController::class, 'store'])->name('production.store');
        Route::post('/production/{batch}/output', [ProductionController::class, 'recordOutput'])->name('production.output');

        // Multi-Stage Material Conversion (3 Reels -> Roll -> Sheet -> Carton)
        Route::get('/production/conversion', [ConversionController::class, 'index'])->name('production.conversion');
        Route::post('/production/conversion', [ConversionController::class, 'storeRun'])->name('production.conversion.store');
        Route::post('/production/conversion/{run}/sheets', [ConversionController::class, 'storeSheetCut'])->name('production.conversion.sheets');
        Route::post('/production/conversion/{run}/cartons', [ConversionController::class, 'storeCartonOutput'])->name('production.conversion.cartons');
        Route::get('/production/conversion/{run}/report', [ConversionController::class, 'report'])->name('production.conversion.report');

        // Inventory: Paper Reels in KG, Top Sheets in Pcs, Deckle sizes
        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::post('/inventory/reels', [InventoryController::class, 'storeReel'])->name('inventory.reels.store');
        Route::post('/inventory/cartons', [InventoryController::class, 'storeCarton'])->name('inventory.cartons.store');
        Route::post('/inventory/top-sheets', [InventoryController::class, 'storeTopSheet'])->name('inventory.top_sheets.store');
        Route::post('/inventory/top-sheets/{topSheet}/adjust', [InventoryController::class, 'adjustTopSheetStock'])->name('inventory.top_sheets.adjust');

        // Party Khata & Local Vendor Shops
        Route::get('/khata', [KhataController::class, 'index'])->name('khata.index');
        Route::get('/khata/{party}', [KhataController::class, 'show'])->name('khata.show');
        Route::post('/khata/party', [KhataController::class, 'storeParty'])->name('khata.party.store');
        Route::post('/khata/party/{party}/update', [KhataController::class, 'updateParty'])->name('khata.party.update');
        Route::post('/khata/party/{party}/adjust', [KhataController::class, 'adjustBalance'])->name('khata.party.adjust');
        Route::post('/khata/voucher', [KhataController::class, 'storeVoucher'])->name('khata.voucher.store');

        // Petty Cash & Daily Factory Safe
        Route::get('/petty-cash', [PettyCashController::class, 'index'])->name('petty_cash.index');
        Route::post('/petty-cash', [PettyCashController::class, 'store'])->name('petty_cash.store');
        Route::get('/petty-cash/print', [PettyCashController::class, 'printSheet'])->name('petty_cash.print');

        // Labor Management & Weekly Thursday Payout
        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('/attendance', [AttendanceController::class, 'save'])->name('attendance.save');
        Route::get('/attendance/weekly', [AttendanceController::class, 'weeklySheet'])->name('attendance.weekly');
        Route::get('/attendance/weekly/print', [AttendanceController::class, 'printWeeklySheet'])->name('attendance.weekly.print');
        Route::post('/attendance/worker', [AttendanceController::class, 'storeWorker'])->name('attendance.worker.store');
        Route::post('/attendance/worker/{worker}/update', [AttendanceController::class, 'updateWorker'])->name('attendance.worker.update');
        Route::post('/attendance/advance', [AttendanceController::class, 'storeAdvance'])->name('attendance.advance.store');
        Route::post('/attendance/advance/{advance}/settle', [AttendanceController::class, 'settleAdvance'])->name('attendance.advance.settle');

        // Bill Book & Document Numbering Configuration
        Route::get('/settings/numbering', [SettingController::class, 'numbering'])->name('settings.numbering');
        Route::post('/settings/numbering', [SettingController::class, 'updateNumbering'])->name('settings.numbering.update');

        // Day-End Reports (Roznamcha)
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    });
});
