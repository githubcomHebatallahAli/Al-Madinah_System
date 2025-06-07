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
            $table->string('invoiceNumber')->unique();
            $table->foreignId('main_pilgrim_id')->nullable()->constrained('pilgrims')->cascadeOnDelete();
            // العلاقات
            $table->foreignId('trip_id')->constrained('trips')->cascadeOnDelete();
            $table->foreignId('office_id')->constrained('offices')->cascadeOnDelete();
            $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();

            $table->foreignId('bus_id')->constrained('buses')->cascadeOnDelete();
            $table->foreignId('bus_driver_id')->constrained('bus_drivers')->cascadeOnDelete();
            $table->foreignId('worker_id')->constrained('workers');
            $table->foreignId('payment_method_type_id')->nullable()->constrained('payment_method_types');
            $table->dateTime('travelDate')->nullable();
            $table->string('travelDateHijri');

            // الحسابات
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->decimal('paidAmount', 10, 2)->default(0);
            $table->integer('bookedSeats')->default(0);

            $table->enum('status', ['pending','approved','rejected','completed','absence'])->default('pending');
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
