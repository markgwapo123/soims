<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ris_items', function (Blueprint $table) {
            $table->decimal('available_quantity', 14, 3)->default(0)->after('requested_quantity');
            $table->decimal('not_available_quantity', 14, 3)->default(0)->after('issue_quantity');
            $table->foreignId('supply_request_item_id')->nullable()->after('item_id')->constrained('supply_request_items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ris_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supply_request_item_id');
            $table->dropColumn(['available_quantity', 'not_available_quantity']);
        });
    }
};
