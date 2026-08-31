<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('id')->constrained('roles')->nullOnDelete();
            $table->string('username', 100)->nullable()->unique()->after('email');
            $table->string('no_wa', 20)->nullable()->after('username');
            $table->string('foto_profil')->nullable()->after('no_wa');
            $table->boolean('status')->default(true)->after('foto_profil');
        });

        Schema::create('cabang_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cabang_id')->constrained('cabang')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['cabang_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cabang_user');

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn(['role_id', 'username', 'no_wa', 'foto_profil', 'status']);
        });
    }
};
