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
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            $table->unsignedBigInteger('shipmentItemsCount')->default(0);
            $table->decimal('totalPrice', 15, 2)->default(0);
            $table->text('description')->nullable();
            $table->decimal('discount', 10, 2)->default(0); // خصم (إن وُجد)
            $table->decimal('paid_amount', 15, 2)->default(0); // المدفوع
            $table->decimal('remaining_amount', 15, 2);     // الباقي
            $table->enum('status', ['paid', 'pending'])->default('pending'); // حالة الفاتورة
            $table->enum('payment_type', ['cash', 'credit', 'bank', 'installment'])->nullable();
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
        Schema::dropIfExists('shipments');
    }
};
