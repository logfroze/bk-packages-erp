<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        if (Schema::hasTable('petty_cash_transactions') && !Schema::hasColumn('petty_cash_transactions', 'receipt_photo_path')) {
            Schema::table('petty_cash_transactions', function (Blueprint $table) {
                $table->string('receipt_photo_path', 255)->nullable();
            });
        }

        if (Schema::hasTable('gate_registers') && !Schema::hasColumn('gate_registers', 'receipt_photo_path')) {
            Schema::table('gate_registers', function (Blueprint $table) {
                $table->string('receipt_photo_path', 255)->nullable();
            });
        }

        if (Schema::hasTable('vouchers') && !Schema::hasColumn('vouchers', 'receipt_photo_path')) {
            Schema::table('vouchers', function (Blueprint $table) {
                $table->string('receipt_photo_path', 255)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('petty_cash_transactions') && Schema::hasColumn('petty_cash_transactions', 'receipt_photo_path')) {
            Schema::table('petty_cash_transactions', function (Blueprint $table) {
                $table->dropColumn('receipt_photo_path');
            });
        }

        if (Schema::hasTable('gate_registers') && Schema::hasColumn('gate_registers', 'receipt_photo_path')) {
            Schema::table('gate_registers', function (Blueprint $table) {
                $table->dropColumn('receipt_photo_path');
            });
        }

        if (Schema::hasTable('vouchers') && Schema::hasColumn('vouchers', 'receipt_photo_path')) {
            Schema::table('vouchers', function (Blueprint $table) {
                $table->dropColumn('receipt_photo_path');
            });
        }
    }
};
