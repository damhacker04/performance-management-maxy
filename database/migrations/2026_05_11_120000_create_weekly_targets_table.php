<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('weekly_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_target_id')
                ->constrained('monthly_targets')
                ->onDelete('cascade');
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade'); // leader yang buat

            $table->string('title');
            $table->text('description')->nullable();

            // Kuantitatif vs kualitatif
            $table->enum('target_type', ['quantitative', 'qualitative'])
                ->default('quantitative');
            $table->decimal('target_value', 10, 2)->nullable();
            $table->string('target_unit', 50)->nullable(); // 'leads', '%', 'clients', dsb.

            // Penomoran minggu: 1=tgl 1-7, 2=8-14, 3=15-21, 4=22-28, 5=29-31
            $table->unsignedTinyInteger('week_number'); // 1-5
            $table->integer('month');
            $table->integer('year');

            $table->timestamps();

            // Index untuk query cepat
            $table->index(['monthly_target_id', 'week_number']);
            $table->index(['year', 'month', 'week_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_targets');
    }
};
