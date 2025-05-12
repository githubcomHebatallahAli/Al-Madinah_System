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
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->cascadeOnDelete();
            $table->foreignId('city_id')->constrained('cities')->cascadeOnDelete();
            $table->string('name');
            $table->string('address');
            $table->unsignedBigInteger('tripsCount')->default(0);
            $table->unsignedBigInteger('storesCount')->default(0);
            $table->unsignedBigInteger('workersCount')->default(0);
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
        Schema::dropIfExists('branches');
    }
};
