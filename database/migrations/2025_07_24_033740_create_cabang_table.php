<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('cabang', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 10)->unique();
            $table->foreignId('perusahaan_id')->constrained('perusahaan');
            $table->string('nama', 100);
            $table->text('alamat')->nullable();
            $table->string('no_hp', 15);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('cabang');
    }
};
