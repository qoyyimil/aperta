<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('edited_photos', function (Blueprint $t) {
            $t->string('thumb_path')->nullable()->after('path');
            $t->string('preview_path')->nullable()->after('thumb_path');
            $t->string('error')->nullable()->after('preview_path');
        });
    }

    public function down(): void
    {
        Schema::table('edited_photos', fn (Blueprint $t) => $t->dropColumn(['thumb_path', 'preview_path', 'error']));
    }
};
