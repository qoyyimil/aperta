<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;

Artisan::command('aperta:admin {email}', function (string $email) {
    User::where('email', $email)->update(['role' => 'admin', 'is_active' => true])
        ? $this->info("{$email} sekarang admin.")
        : $this->error("User {$email} tidak ditemukan.");
})->purpose('Jadikan user sebagai admin');
