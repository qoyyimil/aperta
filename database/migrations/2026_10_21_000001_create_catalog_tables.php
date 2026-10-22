<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('role')->default('photographer')->after('email');
            $t->boolean('is_active')->default(true)->after('role');
        });

        Schema::create('packages', function (Blueprint $t) {
            $t->id();
            $t->string('service');
            $t->string('name');
            $t->unsignedSmallInteger('duration_minutes');
            $t->unsignedInteger('price');
            $t->unsignedSmallInteger('edit_quota');
            $t->unsignedInteger('extra_photo_price');
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('addons', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->unsignedInteger('price');
            $t->unsignedSmallInteger('max_qty')->default(10);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('vouchers', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('type');
            $t->unsignedInteger('value');
            $t->date('starts_on')->nullable();
            $t->date('ends_on')->nullable();
            $t->unsignedInteger('quota')->nullable();
            $t->unsignedInteger('used')->default(0);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');
        Schema::dropIfExists('addons');
        Schema::dropIfExists('packages');
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['role', 'is_active']));
    }
};
