<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        Schema::table('printed_top_sheets', function (Blueprint $table) {
            if (!Schema::hasColumn('printed_top_sheets', 'supplier_name')) {
                $table->string('supplier_name', 150)->nullable()->after('supplier_id');
            }
            if (!Schema::hasColumn('printed_top_sheets', 'received_date')) {
                $table->date('received_date')->nullable()->after('location');
            }
        });
    }

    public function down(): void
    {
        Schema::table('printed_top_sheets', function (Blueprint $table) {
            if (Schema::hasColumn('printed_top_sheets', 'supplier_name')) {
                $table->dropColumn('supplier_name');
            }
            if (Schema::hasColumn('printed_top_sheets', 'received_date')) {
                $table->dropColumn('received_date');
            }
        });
    }
};
