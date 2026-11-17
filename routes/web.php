<?php

use App\Http\Controllers\AiCopilotController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ConversionController;
use App\Http\Controllers\CorrugationController;
use App\Http\Controllers\CustomCardController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\GateRegisterController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\KhataController;
use App\Http\Controllers\OfflineSyncController;
use App\Http\Controllers\PettyCashController;
use App\Http\Controllers\PrintedImportController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

// Public Authentication Routes
Route::get('/ping', fn() => 'pong');
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/login/pin', [AuthController::class, 'loginWithPin'])->name('login.pin');
Route::post('/switch-account', [AuthController::class, 'switchAccount'])->name('account.switch');

// Authenticated ERP Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/lock', [AuthController::class, 'lock'])->name('app.lock');
    Route::get('/setup-pin', [AuthController::class, 'showPinSetup'])->name('pin.setup');
    Route::post('/setup-pin', [AuthController::class, 'storePin'])->name('pin.store');

    // Accessible by all staff (Admin, Accountant, Gate Operator, Manager, Owner)
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/gate-register', [GateRegisterController::class, 'index'])->name('gate.index');
    Route::post('/gate-register', [GateRegisterController::class, 'store'])->name('gate.store');
    Route::post('/gate-register/{gate}/weigh-exit', [GateRegisterController::class, 'updateWeightAndExit'])->name('gate.weigh');
    Route::get('/gate-register/{gate}/slip', [GateRegisterController::class, 'slip'])->name('gate.slip');

    // Factory Internal Chat & Multimedia Messaging (Text, Photos, Audio Voice Notes, PDFs)
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/messages', [ChatController::class, 'getMessages'])->name('chat.messages');
    Route::post('/chat/send', [ChatController::class, 'sendMessage'])->name('chat.send');
    Route::get('/chat/unread-total', [ChatController::class, 'getGlobalUnread'])->name('chat.unread_total');

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

    // Operations & Finance (Mirza Sohail Full Admin, Manager, and Owner View)
    Route::middleware('role:ADMIN,ACCOUNTANT,MANAGER,OWNER')->group(function () {
        // Production Finishing & Conversion (Pasting, Flexo Printing, Thinning Blade/Slitter, Stitching/Binding)
        Route::get('/production', [ProductionController::class, 'index'])->name('production.index');
        Route::post('/production', [ProductionController::class, 'store'])->name('production.store');
        Route::post('/production/{batch}/output', [ProductionController::class, 'recordOutput'])->name('production.output');

        // Printed Imports: Outsourced Custom Top Sheets (Faisalabad, Multan, Lahore, RYK, Jumber) in Pieces (pcs)
        Route::get('/production/printed-imports', [PrintedImportController::class, 'index'])->name('production.printed_imports');
        Route::post('/production/printed-imports', [PrintedImportController::class, 'store'])->name('production.printed_imports.store');
        Route::post('/production/printed-imports/{id}/adjust', [PrintedImportController::class, 'adjustStock'])->name('production.printed_imports.adjust');
        Route::delete('/production/printed-imports/{id}', [PrintedImportController::class, 'destroy'])->name('production.printed_imports.destroy');

        // Corrugations: Nali (1 plain + 1 wavy) & Cardboard Sheets (2-ply Single Face, 3-ply Single Wall, 5-ply Double Wall, 7-ply Triple Wall)
        Route::get('/production/corrugations', [CorrugationController::class, 'index'])->name('production.corrugations');
        Route::post('/production/corrugations', [CorrugationController::class, 'store'])->name('production.corrugations.store');
        Route::delete('/production/corrugations/{id}', [CorrugationController::class, 'destroy'])->name('production.corrugations.destroy');

        // Dynamic Custom Cards & Grids (Add/Delete/Update rows, columns, and field cards across any module)
        Route::post('/custom-cards', [CustomCardController::class, 'store'])->name('custom_cards.store');
        Route::put('/custom-cards/{id}', [CustomCardController::class, 'update'])->name('custom_cards.update');
        Route::delete('/custom-cards/{id}', [CustomCardController::class, 'destroy'])->name('custom_cards.destroy');

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
        Route::get('/attendance/weekly-sheet', [AttendanceController::class, 'weeklySheet']);
        Route::get('/attendance/weekly/print', [AttendanceController::class, 'printWeeklySheet'])->name('attendance.weekly.print');
        Route::post('/attendance/worker', [AttendanceController::class, 'storeWorker'])->name('attendance.worker.store');
        Route::post('/attendance/worker/{worker}/update', [AttendanceController::class, 'updateWorker'])->name('attendance.worker.update');
        Route::post('/attendance/advance', [AttendanceController::class, 'storeAdvance'])->name('attendance.advance.store');
        Route::post('/attendance/advance/{advance}/settle', [AttendanceController::class, 'settleAdvance'])->name('attendance.advance.settle');

        // Bill Book & Document Numbering Configuration (Restricted to Mirza Sohail / Admin)
        Route::middleware('role:ADMIN,ACCOUNTANT')->group(function () {
            Route::get('/settings/numbering', [SettingController::class, 'numbering'])->name('settings.numbering');
            Route::post('/settings/numbering', [SettingController::class, 'updateNumbering'])->name('settings.numbering.update');
        });

        // Day-End Reports (Roznamcha)
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/receipt', [ReportController::class, 'receipt'])->name('reports.receipt');
        Route::get('/reports/export-csv', [ReportController::class, 'exportCsv'])->name('reports.export_csv');
        Route::post('/reports/share-chat', [ReportController::class, 'shareToChat'])->name('reports.share_chat');

        // Document Generation (Delivery Challan & Sales Invoices from physical factory bill receipts)
        Route::get('/documents/delivery-challan/{id?}', [DocumentController::class, 'deliveryChallan'])->name('documents.delivery_challan');
        Route::get('/documents/sales-invoice/{id?}', [DocumentController::class, 'salesInvoice'])->name('documents.sales_invoice');
    });
});
