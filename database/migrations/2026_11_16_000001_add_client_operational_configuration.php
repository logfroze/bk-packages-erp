<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Bill Book & Document Sequences
        Schema::create('document_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('document_type', 40)->unique(); // DELIVERY_CHALLAN, GATE_PASS, SALES_INVOICE
            $table->string('prefix', 20)->default('DC');
            $table->integer('next_number')->default(1);
            $table->integer('padding')->default(4); // e.g. 0001
            $table->string('suffix', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Pre-Printed Top Sheets (Outsourced: Faisalabad, Multan, Lahore, etc.)
        Schema::create('printed_top_sheets', function (Blueprint $table) {
            $table->id();
            $table->string('item_code', 50)->unique();
            $table->string('item_name');
            $table->foreignId('carton_product_id')->nullable()->constrained('carton_products')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->string('sourcing_city', 100)->default('Faisalabad'); // Faisalabad, Multan, Lahore, Rahim Yar Khan, Jumber
            $table->integer('stock_pieces')->default(0); // Tracked strictly in COUNT / PIECES, never weight
            $table->integer('minimum_threshold')->default(500);
            $table->decimal('unit_cost', 10, 2)->default(0.00);
            $table->string('location', 100)->default('Top Sheet Bay 1');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 3. Petty Cash Management & Daily Small Expenses/Sales
        Schema::create('petty_cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_code', 50)->unique();
            $table->string('transaction_type', 30); // CASH_IN, CASH_OUT, COUNTER_SALE
            $table->string('category', 60); // TEA_REFRESHMENT, PETROL_TRANSPORT, HARDWARE_PURCHASE, OFFICE_SUPPLIES, SPARE_PARTS, CHEMICAL_SALE, ROLL_SALE, MISCELLANEOUS
            $table->decimal('amount', 12, 2);
            $table->decimal('running_cash_balance', 12, 2);
            $table->string('spent_by_or_paid_to', 100)->nullable();
            $table->text('description')->nullable();
            $table->foreignId('receipt_voucher_id')->nullable()->constrained('vouchers')->nullOnDelete();
            $table->date('transaction_date')->useCurrent();
            $table->string('recorded_by', 100)->default('Mirza Sohail');
            $table->timestamps();
        });

        // 4. Paper Reel Attributes (Grades & Deckle size in Inches)
        Schema::table('paper_reels', function (Blueprint $table) {
            $table->string('paper_grade', 40)->default('TESTLINER')->after('supplier_id'); // TESTLINER, SUPER_FLUTING, CMP_ULTRA
            $table->decimal('deckle_size_inches', 5, 1)->default(33.0)->after('paper_grade'); // 15.0 to 50.0 in 0.5 increments
        });

        // 5. Corrugator Multi-Reel Support (up to 3 reels) & Top Sheet Linkage
        Schema::table('conversion_runs', function (Blueprint $table) {
            $table->foreignId('reel_3_id')->nullable()->constrained('paper_reels')->nullOnDelete()->after('reel_2_rolls_produced');
            $table->decimal('reel_3_used_kg', 10, 2)->default(0.00)->after('reel_3_id');
            $table->integer('reel_3_rolls_produced')->default(0)->after('reel_3_used_kg');

            $table->foreignId('top_sheet_id')->nullable()->constrained('printed_top_sheets')->nullOnDelete()->after('stage2_loss_sheets');
            $table->integer('top_sheets_used')->default(0)->after('top_sheet_id');
        });

        // 6. Party Classification (Corporate vs. Local Vendor Shops)
        Schema::table('parties', function (Blueprint $table) {
            $table->string('classification', 40)->default('CORPORATE_CLIENT')->after('type'); // CORPORATE_CLIENT, PAPER_MILL, LOCAL_VENDOR_SHOP, TRANSPORTER
            $table->string('vendor_category', 60)->nullable()->after('classification'); // HARDWARE, SPARE_PARTS, PETROL_FUEL, GENERAL_SUPPLIES, OFFICE_SUPPLIES
        });

        // 7. Worker Timings & Overtime Rate Configuration
        Schema::table('workers', function (Blueprint $table) {
            $table->decimal('standard_hours_per_day', 4, 2)->default(8.00)->after('wage_type');
            $table->decimal('overtime_rate_per_hour', 10, 2)->default(150.00)->after('standard_hours_per_day');
        });
    }

    public function down(): void
    {
        Schema::table('workers', function (Blueprint $table) {
            $table->dropColumn(['standard_hours_per_day', 'overtime_rate_per_hour']);
        });

        Schema::table('parties', function (Blueprint $table) {
            $table->dropColumn(['classification', 'vendor_category']);
        });

        Schema::table('conversion_runs', function (Blueprint $table) {
            $table->dropForeign(['reel_3_id']);
            $table->dropForeign(['top_sheet_id']);
            $table->dropColumn(['reel_3_id', 'reel_3_used_kg', 'reel_3_rolls_produced', 'top_sheet_id', 'top_sheets_used']);
        });

        Schema::table('paper_reels', function (Blueprint $table) {
            $table->dropColumn(['paper_grade', 'deckle_size_inches']);
        });

        Schema::dropIfExists('petty_cash_transactions');
        Schema::dropIfExists('printed_top_sheets');
        Schema::dropIfExists('document_sequences');
    }
};
