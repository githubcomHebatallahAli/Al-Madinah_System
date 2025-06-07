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
        Schema::create('bus_invoice_pilgrims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bus_invoice_id')->constrained('bus_invoices')->cascadeOnDelete();
            $table->foreignId('pilgrim_id')->constrained('pilgrims')->cascadeOnDelete();
            $table->string('seatNumber');
            $table->decimal('seatPrice', 10, 2);
            $table->enum('status', ['booked', 'cancelled'])->default('booked');

            $table->unique(['bus_invoice_id', 'pilgrim_id']);
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
        Schema::dropIfExists('bus_invoice_pilgrims');
    }
};
