<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Cek voucher sebelum booking dikirim. Nilai final tetap dihitung ulang saat booking dibuat. */
class VoucherCheckController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30'],
            'amount' => ['required', 'integer', 'min:0'],
        ]);
        $voucher = Voucher::firstWhere('code', strtoupper(trim($data['code'])));
        $problem = $voucher ? $voucher->rejection() : 'Kode voucher tidak ditemukan.';

        return (
            $problem
                ? response()->json(['message' => $problem], 422)
                : response()->json(['code' => $voucher->code, 'discount' => $voucher->discountFor($data['amount'])])
        );
    }
}
