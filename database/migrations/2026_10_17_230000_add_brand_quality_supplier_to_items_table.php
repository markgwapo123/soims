<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table): void {
            $table->string('brand')->nullable()->after('description');
            $table->string('quality')->nullable()->after('brand');
            $table->string('supplier')->nullable()->after('quality');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table): void {
            $table->dropColumn(['brand', 'quality', 'supplier']);
        });
    }
};
