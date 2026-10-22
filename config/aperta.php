<?php

// Aturan bisnis yang bisa diubah tanpa menyentuh kode (SRS 5.5).
return [
    'min_deposit_percent' => (int) env('APERTA_MIN_DEPOSIT_PERCENT', 50), // BR-1
    'full_payment_days_before' => (int) env('APERTA_FULL_PAYMENT_DAYS_BEFORE', 1), // lunas H-1 sesi
    'selection_days' => (int) env('APERTA_SELECTION_DAYS', 7),            // BR-4
    'slots' => ['open' => '07:00', 'close' => '17:00', 'minutes' => 60],
    'min_lead_days' => 1,          // paling cepat H+1
    'booking_window_days' => 90,   // paling jauh 90 hari ke depan
    'cities' => [
        'Surabaya', 
        'Sidoarjo', 
        'Gresik', 
        'Malang', 
        'Yogyakarta',
        'Semarang', 
        'Jakarta', 
        'Bandung'
    ],
    'photo_disk' => env('APERTA_PHOTO_DISK', 'local'),
    'watermark' => resource_path('aperta.png'),
];
