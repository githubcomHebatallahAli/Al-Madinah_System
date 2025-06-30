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
        Schema::create('main_invoice_pilgrims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('main_invoice_id')->constrained('main_invoices')->cascadeOnDelete();
            $table->foreignId('pilgrim_id')->constrained('pilgrims')->cascadeOnDelete();
            $table->string('seatNumber')->nullable();
            $table->string('status')->nullable();
            $table->string('type')->nullable();
            $table->string('position')->nullable();

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
        Schema::dropIfExists('main_invoice_pilgrims');
    }
};
