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
        Schema::create('main_invoice_hotels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('main_invoice_id')->constrained('main_invoices')->cascadeOnDelete();
            $table->foreignId('hotel_id')->constrained('hotels')->cascadeOnDelete();
            $table->enum('need', ['family','single'])->nullable();
            $table->enum('sleep', ['bed', 'room'])->nullable();
            $table->enum('bookingSource', ['MeccaCash','MeccaDelegate','office','otherOffice'])->nullable();
            $table->dateTime('checkInDate')->nullable();
            $table->string('checkInDateHijri')->nullable();
            $table->dateTime('checkOutDate')->nullable();
            $table->string('checkOutDateHijri')->nullable();
            $table->integer('numBed')->nullable();
            $table->integer('numRoom')->nullable();
            $table->integer('numDay')->nullable();
            $table->string('roomNum')->nullable();
            
            $table->decimal('hotelSubtotal', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('main_invoice_hotels');
    }
};
