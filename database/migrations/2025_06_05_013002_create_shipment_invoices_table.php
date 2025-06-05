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
        Schema::create('shipment_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('totalPriceAfterDiscount', 15, 2)->default(0);
            $table->decimal('paidAmount', 15, 2)->default(0);
            $table->decimal('remainingAmount', 15, 2)->nullable();
            $table->enum('invoice', ['paid', 'pending'])->default('pending');
            $table->foreignId('payment_method_type_id')->constrained('payment_method_types')->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('added_by')->nullable();
            $table->string('added_by_type')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->string('updated_by_type')->nullable();
            $table->dateTime('creationDate')->nullable();
            $table->string('creationDateHijri')->nullable();
            $table->enum('status', ['active', 'notActive'])->default('active');
            $table->json('changed_data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_invoices');
    }
};
