<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email');
            $t->string('whatsapp', 20)->index();
            $t->string('instagram')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('bookings', function (Blueprint $t) {
            $t->id();
            $t->string('code', 9)->unique();
            $t->string('access_token', 48)->unique();
            $t->foreignId('client_id')->constrained();
            $t->foreignId('package_id')->nullable()->constrained()->nullOnDelete();
            $t->string('status')->index();

            // jadwal & lokasi
            $t->string('service');
            $t->date('session_date')->index();
            $t->time('starts_at');
            $t->time('ends_at');
            $t->string('city');
            $t->string('location')->nullable();
            $t->decimal('lat', 10, 7)->nullable();
            $t->decimal('lng', 10, 7)->nullable();
            $t->string('institution')->nullable();
            $t->text('notes')->nullable();

            // salinan paket saat booking dibuat (BR-2)
            $t->string('package_name');
            $t->unsignedInteger('package_price');
            $t->unsignedSmallInteger('edit_quota');
            $t->unsignedInteger('extra_photo_price');
            $t->string('voucher_code')->nullable();
            $t->unsignedInteger('discount')->default(0);

            // proofing
            $t->date('selection_deadline')->nullable();
            $t->timestamp('selection_submitted_at')->nullable();
            $t->timestamp('delivered_at')->nullable();
            $t->string('cancel_reason')->nullable();

            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('addon_booking', function (Blueprint $t) {
            $t->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $t->foreignId('addon_id')->constrained();
            $t->unsignedSmallInteger('qty');
            $t->unsignedInteger('price');
            $t->primary(['booking_id', 'addon_id']);
        });

        Schema::create('booking_user', function (Blueprint $t) {
            $t->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->primary(['booking_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_user');
        Schema::dropIfExists('addon_booking');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('clients');
    }
};
