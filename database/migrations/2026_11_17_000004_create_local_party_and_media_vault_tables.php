<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        Schema::dropIfExists('local_party_transactions');
        Schema::dropIfExists('media_vault_items');

        Schema::create('local_party_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('party_id')->nullable()->index();
            $table->string('shop_name', 150)->index();
            $table->string('shop_city', 100)->default('Okara');
            $table->string('transaction_type', 30)->default('WE_BOUGHT')->index(); // WE_BOUGHT (Factory Expense/Inward) vs THEY_BOUGHT (Shop Took/Outward)
            $table->string('item_description', 255);
            $table->string('quantity_details', 100)->nullable(); // e.g. "4 portions", "2 scales", "50 sheets", "20 cartons"
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('payment_status', 50)->default('PAID_CASH'); // PAID_CASH, ADDED_TO_KHATA, SETTLED, PARTIAL
            $table->string('receipt_photo_path', 255)->nullable(); // Photo of bill, grocery pack, physical receipt
            $table->dateTime('transaction_datetime')->useCurrent();
            $table->string('recorded_by_name', 100)->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->json('custom_fields')->nullable(); // Dynamic user-defined field pairs & extra columns
            $table->json('custom_grid')->nullable(); // Dynamic matrix data if customized
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('media_vault_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('title', 150);
            $table->string('category', 60)->default('LOCAL_PARTY_BILL'); // LOCAL_PARTY_BILL, GROCERY_FOOD, PETROL_FUEL, HARDWARE_TOOLS, GATE_CHALLAN, GENERAL
            $table->string('file_path', 255);
            $table->string('file_name', 255);
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size_bytes')->nullable();
            $table->decimal('linked_amount', 12, 2)->nullable();
            $table->string('linked_party_or_shop', 150)->nullable();
            $table->string('source_module', 60)->default('LOCAL_PARTY'); // LOCAL_PARTY, KHATA, PETTY_CASH, GATE, VAULT
            $table->unsignedBigInteger('source_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_vault_items');
        Schema::dropIfExists('local_party_transactions');
    }
};
