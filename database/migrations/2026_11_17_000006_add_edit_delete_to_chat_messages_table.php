<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        if (Schema::hasTable('chat_messages')) {
            Schema::table('chat_messages', function (Blueprint $table) {
                if (!Schema::hasColumn('chat_messages', 'is_edited')) {
                    $table->boolean('is_edited')->default(false);
                }
                if (!Schema::hasColumn('chat_messages', 'edited_at')) {
                    $table->timestamp('edited_at')->nullable();
                }
                if (!Schema::hasColumn('chat_messages', 'is_deleted')) {
                    $table->boolean('is_deleted')->default(false);
                }
                if (!Schema::hasColumn('chat_messages', 'deleted_by')) {
                    $table->unsignedBigInteger('deleted_by')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('chat_messages')) {
            Schema::table('chat_messages', function (Blueprint $table) {
                if (Schema::hasColumn('chat_messages', 'is_edited')) {
                    $table->dropColumn('is_edited');
                }
                if (Schema::hasColumn('chat_messages', 'edited_at')) {
                    $table->dropColumn('edited_at');
                }
                if (Schema::hasColumn('chat_messages', 'is_deleted')) {
                    $table->dropColumn('is_deleted');
                }
                if (Schema::hasColumn('chat_messages', 'deleted_by')) {
                    $table->dropColumn('deleted_by');
                }
            });
        }
    }
};
