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
        Schema::create('buses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->integer('busNum');
            $table->string('busModel');
            $table->string('plateNum');
            $table->integer('seatNum');
            $table->integer('quantity')->default(0);
            $table->decimal('sellingPrice');
            $table->decimal('purchesPrice');
            $table->decimal('profit')->nullable();
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
        Schema::dropIfExists('buses');
    }
};
