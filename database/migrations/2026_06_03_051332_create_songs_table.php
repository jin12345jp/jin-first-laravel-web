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
        Schema::create('songs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artist_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('track_id')->unique();
            $table->string('title');
            $table->text('opening_lyrics')->nullable();
            $table->tinyInteger('difficulty')->default(1)->comment('1:初級, 2:中級, 3:上級');
            $table->text('cover_image_url');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('songs');
        $table->id();
        $table->foreignId('artist_id')->constrained()->onDelete('cascade');
        $table->unsignedBigInteger('track_id')->unique();
        $table->string('title');
        $table->text('opening_lyrics')->nullable();
        $table->tinyInteger('difficulty')->default(1)->comment('1:初級, 2:中級, 3:上級');
        $table->text('cover_image_url');
        $table->timestamps();
    }
};