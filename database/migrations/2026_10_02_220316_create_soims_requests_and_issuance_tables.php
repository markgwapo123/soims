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
        Schema::create('supply_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('request_uuid')->unique();
            $table->string('request_no')->unique();
            $table->foreignId('requestor_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('office_id')->nullable()->constrained()->nullOnDelete();
            $table->string('request_type', 30);
            $table->text('purpose');
            $table->string('status', 30);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('supply_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supply_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->decimal('requested_quantity', 14, 3);
            $table->decimal('confirmed_quantity', 14, 3)->nullable();
            $table->decimal('issued_quantity', 14, 3)->default(0);
            $table->decimal('unavailable_quantity', 14, 3)->default(0);
            $table->decimal('unit_price_snapshot', 14, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('request_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supply_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('approver_id')->constrained('users')->restrictOnDelete();
            $table->string('role_snapshot');
            $table->string('decision', 30);
            $table->text('comments')->nullable();
            $table->timestamp('decided_at');
            $table->timestamps();
        });

        Schema::create('request_modifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supply_request_id')->constrained()->restrictOnDelete();
            $table->foreignId('supply_request_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('field_name');
            $table->json('previous_value');
            $table->json('new_value');
            $table->text('reason');
            $table->foreignId('modified_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('modified_at');
            $table->timestamps();
        });

        Schema::create('receivings', function (Blueprint $table) {
            $table->id();
            $table->uuid('receiving_uuid')->unique();
            $table->string('receiving_no')->unique();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('received_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('received_at');
            $table->string('reference_no')->nullable();
            $table->text('remarks')->nullable();
            $table->string('status', 30);
            $table->timestamps();
        });

        Schema::create('receiving_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receiving_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->foreignId('inventory_lot_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('quantity', 14, 3);
            $table->decimal('unit_cost', 14, 2)->nullable();
            $table->date('expiration_date')->nullable();
            $table->string('classification_snapshot', 20);
            $table->timestamps();
        });

        Schema::create('ris', function (Blueprint $table) {
            $table->id();
            $table->uuid('ris_uuid')->unique();
            $table->string('ris_no')->unique();
            $table->unsignedInteger('revision_no')->default(0);
            $table->foreignId('supply_request_id')->nullable()->constrained()->nullOnDelete();
            $table->string('entity_name');
            $table->string('fund_cluster')->nullable();
            $table->string('division')->nullable();
            $table->string('office')->nullable();
            $table->string('responsibility_center_code')->nullable();
            $table->text('purpose');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('received_printed_name')->nullable();
            $table->string('received_designation')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->string('status', 30);
            $table->timestamps();
        });

        Schema::create('ris_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ris_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->string('stock_no_snapshot');
            $table->string('unit_snapshot');
            $table->text('description_snapshot');
            $table->decimal('requested_quantity', 14, 3);
            $table->decimal('issue_quantity', 14, 3);
            $table->decimal('unit_price', 14, 2);
            $table->decimal('amount', 14, 2);
            $table->boolean('stock_available')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('releases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ris_id')->constrained()->restrictOnDelete();
            $table->foreignId('released_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('released_at');
            $table->string('status', 30);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('releases');
        Schema::dropIfExists('ris_items');
        Schema::dropIfExists('ris');
        Schema::dropIfExists('receiving_items');
        Schema::dropIfExists('receivings');
        Schema::dropIfExists('request_modifications');
        Schema::dropIfExists('request_approvals');
        Schema::dropIfExists('supply_request_items');
        Schema::dropIfExists('supply_requests');
    }
};
