<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        if (Schema::hasTable('petty_cash_transactions')) {
            Schema::table('petty_cash_transactions', function (Blueprint $table) {
                $table->text('receipt_photo_path')->nullable()->change();
            });
        }

        if (Schema::hasTable('gate_registers')) {
            Schema::table('gate_registers', function (Blueprint $table) {
                $table->text('receipt_photo_path')->nullable()->change();
            });
        }

        if (Schema::hasTable('vouchers')) {
            Schema::table('vouchers', function (Blueprint $table) {
                $table->text('receipt_photo_path')->nullable()->change();
            });
        }

        if (Schema::hasTable('media_vault_items')) {
            Schema::table('media_vault_items', function (Blueprint $table) {
                $table->text('file_path')->change();
                $table->string('title', 255)->change();
            });
        }
    }

    public function down(): void
    {
        // No down migration needed for widening column types
    }
};
