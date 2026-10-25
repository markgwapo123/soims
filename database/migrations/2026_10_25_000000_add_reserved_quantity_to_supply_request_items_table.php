<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('supply_request_items', function (Blueprint $table) {
            $table->decimal('reserved_quantity', 15, 3)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('supply_request_items', function (Blueprint $table) {
            $table->dropColumn('reserved_quantity');
        });
    }
};
