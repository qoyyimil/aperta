<?php

namespace App\Policies;

use App\Models\User;

/** Dipakai untuk model yang hanya boleh dikelola admin: paket, add-on, voucher, pembayaran, pengguna (BR-9). */
class AdminOnlyPolicy
{
    public function before(User $user): bool
    {
        return $user->isAdmin();
    }

    // Filament hanya bertanya ke Gate jika method ability-nya ada. Keputusan tetap di before().
    public function viewAny(): bool { return false; }
    public function view(): bool { return false; }
    public function create(): bool { return false; }
    public function update(): bool { return false; }
    public function delete(): bool { return false; }
    public function deleteAny(): bool { return false; }
}
