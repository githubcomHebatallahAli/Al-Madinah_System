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
        Schema::create('worker_logins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_id')->constrained('workers')->cascadeOnDelete()->unique(); // علاقة 1:1
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->foreignId('role_id')->nullable()->constrained('roles')->cascadeOnDelete();
            $table->foreignId('added_by')->nullable()->constrained('workers');
            $table->json('changed_data')->nullable();
            $table->dateTime('creationDate')->nullable();
            $table->string('creationDateHijri')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('worker_logins');
    }
};
