<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sensus_ekonomi_replacement_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('replacement_id')
                ->constrained('sensus_ekonomi_petugas_replacements')
                ->cascadeOnDelete();
            $table->string('document_type', 32);
            $table->unsignedTinyInteger('termin')->nullable();
            $table->string('nomor_dokumen');
            $table->date('tanggal_dokumen')->nullable();
            $table->text('file_path');
            $table->timestamp('uploaded_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['replacement_id', 'document_type', 'termin'],
                'se_replacement_document_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sensus_ekonomi_replacement_documents');
    }
};
