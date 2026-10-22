<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proof_photos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $t->unsignedSmallInteger('frame');
            $t->string('filename');
            $t->string('thumb_path')->nullable();
            $t->string('preview_path')->nullable();
            $t->boolean('is_selected')->default(false);
            $t->string('note', 300)->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->unique(['booking_id', 'filename']);
        });

        Schema::create('edited_photos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $t->foreignId('proof_photo_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('filename');
            $t->string('path');
            $t->unsignedInteger('size');
            $t->timestamps();
            $t->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edited_photos');
        Schema::dropIfExists('proof_photos');
    }
};
