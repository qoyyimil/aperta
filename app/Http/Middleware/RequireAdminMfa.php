<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Auth\MultiFactor\Http\Middleware\EnsureMultiFactorAuthenticationIsEnabled;
use Illuminate\Http\Request;

/** MFA wajib untuk admin karena bisa melihat semua data klien dan pembayaran. */
class RequireAdminMfa extends EnsureMultiFactorAuthenticationIsEnabled
{
    public function handle(Request $request, Closure $next): mixed
    {
        return $request->user()?->isAdmin() ? parent::handle($request, $next) : $next($request);
    }
}
