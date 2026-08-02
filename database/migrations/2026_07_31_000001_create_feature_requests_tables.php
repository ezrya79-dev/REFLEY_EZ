<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feature_requests', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120);
            $table->text('description');
            $table->string('category');
            $table->string('status')->default('proposed');
            $table->string('priority')->default('none');
            $table->string('difficulty')->default('unknown');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'category']);
        });

        Schema::create('feature_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feature_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            // Un seul vote par personne et par idée — garanti par la base,
            // pas seulement par l'interface.
            $table->unique(['feature_request_id', 'user_id']);
        });

        Schema::create('feature_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feature_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_comments');
        Schema::dropIfExists('feature_votes');
        Schema::dropIfExists('feature_requests');
    }
};
