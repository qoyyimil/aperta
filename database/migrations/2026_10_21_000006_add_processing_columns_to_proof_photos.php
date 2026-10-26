<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('proof_photos', function (Blueprint $t) {
            $t->string('source_path')->nullable()->after('filename');
            $t->string('error')->nullable()->after('preview_path');
        });
    }

    public function down(): void
    {
        Schema::table('proof_photos', fn(Blueprint $t) => $t->dropColumn(['source_path', 'error']));
    }
};
