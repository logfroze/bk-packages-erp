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
use App\Http\Controllers\MediaVaultController;
use App\Http\Controllers\OfflineSyncController;
use App\Http\Controllers\PettyCashController;
use App\Http\Controllers\PrintedImportController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

// Block direct probing of sensitive files (OWASP 2025 Check 10.4)
Route::match(['get', 'post', 'head'], '/{file}', function () {
    abort(404);
})->where('file', '.*(\.env|\.git|\.htaccess|composer\.(json|lock)|package\.(json|lock)|\.sql).*');

// Public Authentication Routes (Guarded with Rate Limiting: OWASP 2025 Checks 2.4 & 6.1)
Route::get('/ping', fn() => 'pong');
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.submit');
Route::post('/login/pin', [AuthController::class, 'loginWithPin'])->middleware('throttle:5,1')->name('login.pin');
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
    Route::match(['put', 'patch', 'post'], '/gate-register/{gate}', [GateRegisterController::class, 'update'])->name('gate.update');
    Route::delete('/gate-register/{gate}', [GateRegisterController::class, 'destroy'])->name('gate.destroy');
    Route::post('/gate-register/{gate}/delete', [GateRegisterController::class, 'destroy']);
    Route::post('/gate-register/{gate}/weigh-exit', [GateRegisterController::class, 'updateWeightAndExit'])->name('gate.weigh');
    Route::get('/gate-register/{gate}/slip', [GateRegisterController::class, 'slip'])->name('gate.slip');

    // Factory Internal Chat & Multimedia Messaging (Text, Photos, Audio Voice Notes, PDFs)
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/messages', [ChatController::class, 'getMessages'])->name('chat.messages');
    Route::post('/chat/send', [ChatController::class, 'sendMessage'])->name('chat.send');
    Route::post('/chat/messages/{message}/edit', [ChatController::class, 'editMessage'])->name('chat.message.edit');
    Route::delete('/chat/messages/{message}', [ChatController::class, 'deleteMessage'])->name('chat.message.delete');
    Route::post('/chat/delete-multiple', [ChatController::class, 'deleteMultiple'])->name('chat.delete_multiple');
    Route::post('/chat/clear', [ChatController::class, 'clearChat'])->name('chat.clear');
    Route::post('/chat/auto-clean', [ChatController::class, 'autoClean'])->name('chat.auto_clean');
    Route::get('/chat/unread-total', [ChatController::class, 'getGlobalUnread'])->name('chat.unread_total');

    // Offline PWA Sync API
    Route::get('/offline-sync/ping', [OfflineSyncController::class, 'ping'])->name('offline.ping');
    Route::post('/offline-sync/replay', [OfflineSyncController::class, 'replay'])->name('offline.replay');

    // Google AI Studio Factory Assistant
    Route::get('/ai-copilot/conversations', [AiCopilotController::class, 'conversations'])->name('ai.conversations.index');
    Route::get('/ai-copilot/conversations/{conversation}', [AiCopilotController::class, 'showConversation'])->name('ai.conversations.show');
    Route::delete('/ai-copilot/conversations/{conversation}', [AiCopilotController::class, 'destroyConversation'])->name('ai.conversations.destroy');
    Route::post('/ai-copilot/messages/{message}/dismiss', [AiCopilotController::class, 'dismissAction'])->name('ai.messages.dismiss');
    Route::post('/ai-copilot/chat', [AiCopilotController::class, 'chat'])->middleware('throttle:15,1')->name('ai.chat');
    Route::post('/ai-copilot/execute', [AiCopilotController::class, 'execute'])->middleware('throttle:15,1')->name('ai.execute');

    // Operations & Finance (Mirza Sohail Full Admin, Manager, and Owner View)
    Route::middleware('role:ADMIN,ACCOUNTANT,MANAGER,OWNER')->group(function () {
        // Production Finishing & Conversion (Pasting, Flexo Printing, Thinning Blade/Slitter, Stitching/Binding)
        Route::get('/production', [ProductionController::class, 'index'])->name('production.index');
        Route::post('/production', [ProductionController::class, 'store'])->name('production.store');
        Route::match(['put', 'patch', 'post'], '/production/{batch}', [ProductionController::class, 'update'])->name('production.update');
        Route::match(['delete', 'post'], '/production/{batch}/delete', [ProductionController::class, 'destroy']);
        Route::delete('/production/{batch}', [ProductionController::class, 'destroy'])->name('production.destroy');
        Route::post('/production/{batch}/output', [ProductionController::class, 'recordOutput'])->name('production.output');

        // Printed Imports: Outsourced Custom Top Sheets (Faisalabad, Multan, Lahore, RYK, Jumber) in Pieces (pcs)
        Route::get('/production/printed-imports', [PrintedImportController::class, 'index'])->name('production.printed_imports');
        Route::post('/production/printed-imports', [PrintedImportController::class, 'store'])->name('production.printed_imports.store');
        Route::match(['put', 'patch', 'post'], '/production/printed-imports/{id}', [PrintedImportController::class, 'update'])->name('production.printed_imports.update');
        Route::post('/production/printed-imports/{id}/adjust', [PrintedImportController::class, 'adjustStock'])->name('production.printed_imports.adjust');
        Route::match(['delete', 'post'], '/production/printed-imports/{id}/delete', [PrintedImportController::class, 'destroy']);
        Route::delete('/production/printed-imports/{id}', [PrintedImportController::class, 'destroy'])->name('production.printed_imports.destroy');

        // Corrugations: Nali (1 plain + 1 wavy) & Cardboard Sheets (2-ply Single Face, 3-ply Single Wall, 5-ply Double Wall, 7-ply Triple Wall)
        Route::get('/production/corrugations', [CorrugationController::class, 'index'])->name('production.corrugations');
        Route::post('/production/corrugations', [CorrugationController::class, 'store'])->name('production.corrugations.store');
        Route::match(['put', 'patch', 'post'], '/production/corrugations/{id}', [CorrugationController::class, 'update'])->name('production.corrugations.update');
        Route::match(['delete', 'post'], '/production/corrugations/{id}/delete', [CorrugationController::class, 'destroy']);
        Route::delete('/production/corrugations/{id}', [CorrugationController::class, 'destroy'])->name('production.corrugations.destroy');

        // Dynamic Custom Cards & Grids (Add/Delete/Update rows, columns, and field cards across any module)
        Route::post('/custom-cards', [CustomCardController::class, 'store'])->name('custom_cards.store');
        Route::match(['put', 'patch', 'post'], '/custom-cards/{id}', [CustomCardController::class, 'update'])->name('custom_cards.update');
        Route::match(['delete', 'post'], '/custom-cards/{id}/delete', [CustomCardController::class, 'destroy']);
        Route::delete('/custom-cards/{id}', [CustomCardController::class, 'destroy'])->name('custom_cards.destroy');

        // Multi-Stage Material Conversion (3 Reels -> Roll -> Sheet -> Carton)
        Route::get('/production/conversion', [ConversionController::class, 'index'])->name('production.conversion');
        Route::post('/production/conversion', [ConversionController::class, 'storeRun'])->name('production.conversion.store');
        Route::post('/production/conversion/{run}/sheets', [ConversionController::class, 'storeSheetCut'])->name('production.conversion.sheets');
        Route::post('/production/conversion/{run}/cartons', [ConversionController::class, 'storeCartonOutput'])->name('production.conversion.cartons');
        Route::get('/production/conversion/{run}/report', [ConversionController::class, 'report'])->name('production.conversion.report');
        Route::match(['delete', 'post'], '/production/conversion/{run}/delete', [ConversionController::class, 'destroy']);
        Route::delete('/production/conversion/{run}', [ConversionController::class, 'destroy'])->name('production.conversion.destroy');

        // Inventory: Paper Reels in KG, Top Sheets in Pcs, Deckle sizes
        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::post('/inventory/reels', [InventoryController::class, 'storeReel'])->name('inventory.reels.store');
        Route::match(['put', 'patch', 'post'], '/inventory/reels/{reel}', [InventoryController::class, 'updateReel'])->name('inventory.reels.update');
        Route::match(['delete', 'post'], '/inventory/reels/{reel}/delete', [InventoryController::class, 'destroyReel']);
        Route::delete('/inventory/reels/{reel}', [InventoryController::class, 'destroyReel'])->name('inventory.reels.destroy');
        Route::post('/inventory/cartons', [InventoryController::class, 'storeCarton'])->name('inventory.cartons.store');
        Route::match(['put', 'patch', 'post'], '/inventory/cartons/{carton}', [InventoryController::class, 'updateCarton'])->name('inventory.cartons.update');
        Route::match(['delete', 'post'], '/inventory/cartons/{carton}/delete', [InventoryController::class, 'destroyCarton']);
        Route::delete('/inventory/cartons/{carton}', [InventoryController::class, 'destroyCarton'])->name('inventory.cartons.destroy');
        Route::post('/inventory/top-sheets', [InventoryController::class, 'storeTopSheet'])->name('inventory.top_sheets.store');
        Route::post('/inventory/top-sheets/{topSheet}/adjust', [InventoryController::class, 'adjustTopSheetStock'])->name('inventory.top_sheets.adjust');

        // Party Khata & Local Vendor Shops
        Route::get('/khata', [KhataController::class, 'index'])->name('khata.index');
        Route::get('/khata/{party}', [KhataController::class, 'show'])->name('khata.show');
        Route::post('/khata/party', [KhataController::class, 'storeParty'])->name('khata.party.store');
        Route::match(['put', 'patch', 'post'], '/khata/party/{party}/update', [KhataController::class, 'updateParty'])->name('khata.party.update');
        Route::match(['put', 'patch', 'post'], '/khata/party/{party}', [KhataController::class, 'updateParty']);
        Route::post('/khata/party/{party}/adjust', [KhataController::class, 'adjustBalance'])->name('khata.party.adjust');
        Route::post('/khata/voucher', [KhataController::class, 'storeVoucher'])->name('khata.voucher.store');
        Route::match(['put', 'patch', 'post'], '/khata/voucher/{voucher}', [KhataController::class, 'updateVoucher'])->name('khata.voucher.update');
        Route::match(['delete', 'post'], '/khata/voucher/{voucher}/delete', [KhataController::class, 'destroyVoucher']);
        Route::delete('/khata/voucher/{voucher}', [KhataController::class, 'destroyVoucher'])->name('khata.voucher.destroy');

        // Local Party Small Expenses, Barter Ledger & Camera Bill Uploads
        Route::post('/khata/local-transactions', [KhataController::class, 'storeLocalTransaction'])->name('khata.local_transactions.store');
        Route::match(['put', 'patch', 'post'], '/khata/local-transactions/{id}', [KhataController::class, 'updateLocalTransaction'])->name('khata.local_transactions.update');
        Route::match(['delete', 'post'], '/khata/local-transactions/{id}/delete', [KhataController::class, 'destroyLocalTransaction']);
        Route::delete('/khata/local-transactions/{id}', [KhataController::class, 'destroyLocalTransaction'])->name('khata.local_transactions.destroy');

        // Central Factory Media & Receipt Vault (Photos, Bills, Grocery Packs, Gate Slips)
        Route::get('/media-vault', [MediaVaultController::class, 'index'])->name('media_vault.index');
        Route::post('/media-vault', [MediaVaultController::class, 'store'])->name('media_vault.store');
        Route::match(['put', 'patch', 'post'], '/media-vault/{item}', [MediaVaultController::class, 'update'])->name('media_vault.update');
        Route::match(['delete', 'post'], '/media-vault/{item}/delete', [MediaVaultController::class, 'destroy']);
        Route::delete('/media-vault/{item}', [MediaVaultController::class, 'destroy'])->name('media_vault.destroy');

        // Petty Cash & Daily Factory Safe
        Route::get('/petty-cash', [PettyCashController::class, 'index'])->name('petty_cash.index');
        Route::post('/petty-cash', [PettyCashController::class, 'store'])->name('petty_cash.store');
        Route::match(['put', 'patch', 'post'], '/petty-cash/{id}', [PettyCashController::class, 'update'])->name('petty_cash.update');
        Route::match(['delete', 'post'], '/petty-cash/{id}/delete', [PettyCashController::class, 'destroy']);
        Route::delete('/petty-cash/{id}', [PettyCashController::class, 'destroy'])->name('petty_cash.destroy');
        Route::get('/petty-cash/print', [PettyCashController::class, 'printSheet'])->name('petty_cash.print');

        // Labor Management & Weekly Thursday Payout
        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('/attendance', [AttendanceController::class, 'save'])->name('attendance.save');
        Route::match(['delete', 'post'], '/attendance/delete-worker', function (\Illuminate\Http\Request $request) {
            if ($request->filled('worker_id')) {
                $worker = \App\Models\Worker::find($request->input('worker_id'));
                if ($worker) {
                    return app(\App\Http\Controllers\AttendanceController::class)->destroyWorker($worker);
                }
            }
            return redirect()->route('attendance.index');
        });
        Route::delete('/attendance', function (\Illuminate\Http\Request $request) {
            if ($request->filled('worker_id')) {
                $worker = \App\Models\Worker::find($request->input('worker_id'));
                if ($worker) {
                    return app(\App\Http\Controllers\AttendanceController::class)->destroyWorker($worker);
                }
            }
            return redirect()->route('attendance.index');
        });
        Route::get('/attendance/weekly', [AttendanceController::class, 'weeklySheet'])->name('attendance.weekly');
        Route::get('/attendance/weekly-sheet', [AttendanceController::class, 'weeklySheet']);
        Route::get('/attendance/weekly/print', [AttendanceController::class, 'printWeeklySheet'])->name('attendance.weekly.print');
        Route::post('/attendance/worker', [AttendanceController::class, 'storeWorker'])->name('attendance.worker.store');
        Route::match(['post', 'put', 'patch'], '/attendance/worker/{worker}/update', [AttendanceController::class, 'updateWorker'])->name('attendance.worker.update');
        Route::match(['post', 'put', 'patch'], '/attendance/worker/{worker}', [AttendanceController::class, 'updateWorker']);
        Route::match(['delete', 'post'], '/attendance/worker/{worker}/delete', [AttendanceController::class, 'destroyWorker']);
        Route::delete('/attendance/worker/{worker}', [AttendanceController::class, 'destroyWorker'])->name('attendance.worker.destroy');
        Route::post('/attendance/advance', [AttendanceController::class, 'storeAdvance'])->name('attendance.advance.store');
        Route::post('/attendance/advance/{advance}/settle', [AttendanceController::class, 'settleAdvance'])->name('attendance.advance.settle');
        Route::match(['delete', 'post'], '/attendance/advance/{advance}/delete', [AttendanceController::class, 'destroyAdvance']);
        Route::delete('/attendance/advance/{advance}', [AttendanceController::class, 'destroyAdvance'])->name('attendance.advance.destroy');

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
