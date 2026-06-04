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
        Schema::create('daily_task_evidences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_task_entry_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['link', 'file', 'image']);
            $table->string('label')->nullable();
            $table->text('path_or_url');
            $table->timestamps();
        });

        // Migrasi data lama dari daily_task_entries ke daily_task_evidences
        $entries = DB::table('daily_task_entries')->whereNotNull('proof_url')->orWhereNotNull('proof_file')->get();
        foreach ($entries as $entry) {
            if (!empty($entry->proof_url)) {
                DB::table('daily_task_evidences')->insert([
                    'daily_task_entry_id' => $entry->id,
                    'type'                => 'link',
                    'label'               => 'Link Bukti Lama',
                    'path_or_url'         => $entry->proof_url,
                    'created_at'          => $entry->created_at,
                    'updated_at'          => $entry->updated_at,
                ]);
            }
            if (!empty($entry->proof_file)) {
                // Tentukan apakah image atau file (PDF) berdasarkan ekstensi
                $ext = strtolower(pathinfo($entry->proof_file, PATHINFO_EXTENSION));
                $type = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']) ? 'image' : 'file';
                
                DB::table('daily_task_evidences')->insert([
                    'daily_task_entry_id' => $entry->id,
                    'type'                => $type,
                    'label'               => $type === 'image' ? 'Screenshot Lama' : 'File Bukti Lama',
                    'path_or_url'         => $entry->proof_file,
                    'created_at'          => $entry->created_at,
                    'updated_at'          => $entry->updated_at,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_task_evidences');
    }
};
