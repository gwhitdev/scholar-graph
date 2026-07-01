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
        Schema::create('paper_enrichments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paper_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('semantic_scholar_id', 64)->nullable();
            $table->text('tldr')->nullable();
            $table->integer('influential_citation_count')->nullable();
            $table->jsonb('related_paper_ids')->nullable();
            $table->timestamp('enriched_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paper_enrichments');
    }
};
