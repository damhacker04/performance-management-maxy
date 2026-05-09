<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('daily_task_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('monthly_target_id')->constrained()->onDelete('cascade');
            $table->text('task_description');
            $table->integer('duration_minutes');
            $table->enum('status', ['selesai', 'dalam_proses', 'terhambat']);
            $table->text('notes')->nullable();
            $table->date('task_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_task_entries');
    }
};