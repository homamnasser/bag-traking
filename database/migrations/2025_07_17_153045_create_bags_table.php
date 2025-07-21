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
        Schema::create('bags', function (Blueprint $table) {
            $table->id();
            $table->string('bag_id')->unique();
            $table->enum('status', ['available', 'unavailable'])->default('available');
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
            $table->string('qr_code_path')->nullable();
            $table->string('last_update_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bags');
    }
};
