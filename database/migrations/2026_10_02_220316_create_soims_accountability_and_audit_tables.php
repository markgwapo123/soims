<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('accountable_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->string('property_no')->unique();
            $table->string('barcode')->unique();
            $table->string('serial_no')->nullable()->unique();
            $table->date('date_acquired')->nullable();
            $table->decimal('unit_value', 14, 2)->nullable();
            $table->string('status', 30);
            $table->foreignId('current_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('current_office_id')->nullable()->constrained('offices')->nullOnDelete();
            $table->string('current_location')->nullable();
            $table->foreignId('current_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->foreign('accountable_item_id')->references('id')->on('accountable_items')->nullOnDelete();
        });

        Schema::create('item_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accountable_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('assigned_to_office_id')->nullable()->constrained('offices')->nullOnDelete();
            $table->string('location');
            $table->foreignId('assigned_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('assigned_at');
            $table->timestamp('released_at')->nullable();
            $table->text('release_reason')->nullable();
            $table->string('status', 30);
            $table->timestamps();
            $table->index(['accountable_item_id', 'released_at']);
        });

        Schema::create('item_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accountable_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('from_assignment_id')->nullable()->constrained('item_assignments')->nullOnDelete();
            $table->foreignId('to_assignment_id')->nullable()->constrained('item_assignments')->nullOnDelete();
            $table->string('from_location')->nullable();
            $table->string('to_location')->nullable();
            $table->foreignId('transferred_by')->constrained('users')->restrictOnDelete();
            $table->text('reason')->nullable();
            $table->timestamp('transferred_at');
            $table->timestamps();
        });

        Schema::create('property_returns', function (Blueprint $table) {
            $table->id();
            $table->uuid('property_return_uuid')->unique();
            $table->string('prs_no')->unique();
            $table->string('document_no')->nullable();
            $table->string('issue_no')->nullable();
            $table->unsignedInteger('revision_no')->default(0);
            $table->string('office_name');
            $table->string('purpose', 30);
            $table->string('received_from')->nullable();
            $table->string('returned_to')->nullable();
            $table->string('end_user')->nullable();
            $table->text('certification')->nullable();
            $table->foreignId('returned_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('received_at');
            $table->string('status', 30);
            $table->timestamps();
        });

        Schema::create('property_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('accountable_item_id')->constrained()->restrictOnDelete();
            $table->string('property_no_snapshot');
            $table->text('description_snapshot');
            $table->decimal('quantity', 14, 3);
            $table->decimal('unit_value', 14, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('physical_inventories', function (Blueprint $table) {
            $table->id();
            $table->uuid('physical_inventory_uuid')->unique();
            $table->string('inventory_no')->unique();
            $table->foreignId('conducted_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('office_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('conducted_at');
            $table->string('status', 30);
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('physical_inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('physical_inventory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('accountable_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('article');
            $table->text('description');
            $table->date('date_acquired')->nullable();
            $table->string('old_property_no')->nullable();
            $table->string('new_property_no')->nullable();
            $table->string('unit_of_measure')->nullable();
            $table->decimal('unit_value', 14, 2)->nullable();
            $table->decimal('quantity_per_property_card', 14, 3);
            $table->decimal('quantity_per_physical_count', 14, 3);
            $table->decimal('shortage_quantity', 14, 3)->default(0);
            $table->decimal('overage_quantity', 14, 3)->default(0);
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('role_key')->nullable();
            $table->string('type');
            $table->string('title');
            $table->text('message');
            $table->string('related_type')->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['related_type', 'related_id']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('role_snapshot')->nullable();
            $table->string('action');
            $table->string('module');
            $table->string('record_type')->nullable();
            $table->unsignedBigInteger('record_id')->nullable();
            $table->json('previous_value')->nullable();
            $table->json('new_value')->nullable();
            $table->text('reason')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();
            $table->index(['record_type', 'record_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('physical_inventory_items');
        Schema::dropIfExists('physical_inventories');
        Schema::dropIfExists('property_return_items');
        Schema::dropIfExists('property_returns');
        Schema::dropIfExists('item_transfers');
        Schema::dropIfExists('item_assignments');
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropForeign(['accountable_item_id']);
        });
        Schema::dropIfExists('accountable_items');
    }
};
