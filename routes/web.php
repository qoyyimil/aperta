<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{AvailabilityController, BookingController, TrackController, VoucherCheckController};
use App\Http\Controllers\SelectionController;
use App\Http\Controllers\ResultController;

Route::get('/', [BookingController::class, 'create'])->name('booking.create');
Route::post('/booking', [BookingController::class, 'store'])->middleware('throttle:10,1')->name('booking.store');
Route::get('/availability', AvailabilityController::class)->middleware('throttle:60,1');
Route::get('/voucher', VoucherCheckController::class)->middleware('throttle:20,1');

Route::get('/lacak', [TrackController::class, 'find'])->name('track.find');
Route::post('/lacak', [TrackController::class, 'lookup'])->name('track.lookup');
Route::get('/lacak/{booking:access_token}', [TrackController::class, 'show'])->name('track.show');

Route::prefix('/lacak/{booking:access_token}/foto')->name('selection.')->scopeBindings()->group(function () {
    Route::get('/', [SelectionController::class, 'show'])->name('show');
    Route::patch('/{proof}', [SelectionController::class, 'update'])->middleware('throttle:120,1')->name('update');
    Route::post('/kirim', [SelectionController::class, 'submit'])->middleware('throttle:10,1')->name('submit');
});
Route::prefix('/lacak/{booking:access_token}/hasil')->name('results.')->scopeBindings()->middleware('throttle:60,1')->group(function () {
    Route::get('/', [ResultController::class, 'show'])->name('show');
    Route::get('/zip', [ResultController::class, 'zip'])->middleware('throttle:3,1,zip')->name('zip');
    Route::get('/{result}/unduh', [ResultController::class, 'download'])->name('download');
});
