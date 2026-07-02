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
        Schema::table('papers', function (Blueprint $table) {
            $table->string('openalex_id', 64)->nullable()->index()->after('semantic_scholar_id');
            $table->integer('cited_by_count')->nullable()->after('pages');
            $table->jsonb('referenced_works')->nullable()->after('cited_by_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('papers', function (Blueprint $table) {
            $table->dropIndex(['openalex_id']);
            $table->dropColumn(['openalex_id', 'cited_by_count', 'referenced_works']);
        });
    }
};
