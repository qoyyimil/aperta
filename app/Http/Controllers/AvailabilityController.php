<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Support\Availability;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/** Jam mulai yang tersedia untuk tanggal & paket tertentu, dipanggil oleh form booking. */
class AvailabilityController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate(['date' => ['required', 'date_format:Y-m-d'], 'package' => ['required', 'integer']]);
        $package = Package::active()->findOrFail($data['package']);

        return response()->json([
            'times' => Availability::make()->startTimes(Carbon::parse($data['date']), $package->duration_minutes),
        ]);
    }
}
