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
        Schema::create('bus_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bus_trip_id')->constrained('bus_trips')->cascadeOnDelete();

            $table->string('invoiceNumber')->unique();
            $table->foreignId('main_pilgrim_id')->nullable()->constrained('pilgrims')->cascadeOnDelete();
            // العلاقات

            $table->foreignId('office_id')->constrained('offices')->cascadeOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained('campaigns')->cascadeOnDelete();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('worker_id')->nullable()->constrained('workers');
            $table->foreignId('payment_method_type_id')->nullable()->constrained('payment_method_types');
            $table->unsignedBigInteger('pilgrimsCount')->default(0);


            // الحسابات
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->decimal('paidAmount', 10, 2)->default(0);
            $table->decimal('seatPrice', 10, 2);


            $table->enum('invoiceStatus', ['pending','approved','rejected','completed','absence'])->default('pending');
            $table->text('reason')->nullable();
            $table->enum('paymentStatus', ['pending','paid','refunded'])->default('pending');
            $table->unsignedBigInteger('added_by')->nullable();
            $table->string('added_by_type')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->string('updated_by_type')->nullable();
            $table->dateTime('creationDate')->nullable();
            $table->string('creationDateHijri')->nullable();
            $table->json('changed_data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bus_invoices');
    }
};
