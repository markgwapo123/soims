<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supply_request_items', function (Blueprint $table) {
            // Null means the confirmed item is the originally requested item.
            $table->foreignId('confirmed_item_id')->nullable()->after('item_id')->constrained('items')->restrictOnDelete();
        });

        Schema::table('request_modifications', function (Blueprint $table) {
            $table->string('role_snapshot')->nullable()->after('modified_by');
        });
    }

    public function down(): void
    {
        Schema::table('request_modifications', function (Blueprint $table) {
            $table->dropColumn('role_snapshot');
        });

        Schema::table('supply_request_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('confirmed_item_id');
        });
    }
};
