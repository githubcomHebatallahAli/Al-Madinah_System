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
        Schema::create('hotel_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bus_trip_id')->nullable()->constrained('bus_trips')->cascadeOnDelete();
            $table->foreignId('hotel_id')->constrained('hotels')->cascadeOnDelete();
            $table->foreignId('main_pilgrim_id')->nullable()->constrained('pilgrims')->cascadeOnDelete();
            $table->foreignId('payment_method_type_id')->nullable()->constrained('payment_method_types');
            $table->unsignedBigInteger('pilgrimsCount')->default(0);
            $table->enum('need', ['family', 'single']);
            $table->enum('sleep', ['bed', 'room']);
            $table->enum('bookingSource', ['MeccaCash', 'MeccaDelegate','office','otherOffice']);
            $table->dateTime('residenceDate')->nullable();
            $table->string('residenceDateHijri')->nullable();
            $table->integer('numDay');
            $table->integer('roomNum')->nullable();
            $table->text('description')->nullable();

            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->decimal('paidAmount', 10, 2)->default(0);

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
        Schema::dropIfExists('hotel_invoices');
    }
};
