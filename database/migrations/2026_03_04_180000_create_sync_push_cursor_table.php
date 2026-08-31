<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sync_push_cursor', function (Blueprint $table) {
            $table->id();
            $table->string('target', 50)->default('cloud');
            $table->string('dataset', 100);
            $table->timestamp('last_updated_at')->nullable();
            $table->unsignedBigInteger('last_pk')->default(0);
            $table->unsignedInteger('last_sent_rows')->default(0);
            $table->timestamp('last_success_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->unique(['target', 'dataset']);
            $table->index(['target', 'dataset']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_push_cursor');
    }
};
