<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workers', function (Blueprint $table) {
            $table->decimal('standard_hours_per_day', 4, 2)->default(8.00)->nullable()->change();
            $table->decimal('overtime_rate_per_hour', 10, 2)->default(0.00)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('workers', function (Blueprint $table) {
            $table->decimal('standard_hours_per_day', 4, 2)->default(8.00)->nullable(false)->change();
            $table->decimal('overtime_rate_per_hour', 10, 2)->default(150.00)->nullable(false)->change();
        });
    }
};
