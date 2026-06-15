<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('global_search_documents', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('entity_type')->index();
            $table->string('entity_id')->index();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->text('search_text')->nullable();
            $table->string('route');
            $table->json('metadata')->nullable();
            $table->timestamp('source_created_at')->nullable();
            $table->timestamp('source_updated_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('global_search_documents');
    }
};
