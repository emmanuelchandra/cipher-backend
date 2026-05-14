<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->dateTime('check_in');
            $table->dateTime('check_out')->nullable();
            $table->enum('status', ['on_time', 'late', 'absent'])->default('on_time');
            $table->integer('overtime_minutes')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'check_in']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
