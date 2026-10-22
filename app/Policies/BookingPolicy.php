<?php

namespace App\Policies;

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\User;

/** Admin boleh semua. Fotografer/editor hanya booking yang ditugaskan (BR-10). */
class BookingPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ?: null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Booking $booking): bool
    {
        return $booking->crew->contains($user);
    }

    /** Membuat, mengubah, dan menghapus booking hanya untuk admin (lewat before()). */
    public function create(): bool { return false; }
    public function update(): bool { return false; }
    public function delete(): bool { return false; }
    public function deleteAny(): bool { return false; }

    /** Unggah & publikasi proof: fotografer yang ditugaskan (PRF). */
    public function manageProofs(User $user, Booking $booking): bool
    {
        return $user->role === UserRole::Photographer && $this->view($user, $booking);
    }

    /** Unggah & kirim hasil edit: fotografer atau editor yang ditugaskan (DLV). */
    public function manageResults(User $user, Booking $booking): bool
    {
        return $this->view($user, $booking);
    }

    /** Selain admin, hanya fotografer yang ditugaskan boleh menandai sesi selesai. */
    public function transition(User $user, Booking $booking, BookingStatus $to): bool
    {
        return $to === BookingStatus::Shot
            && $user->role === UserRole::Photographer
            && $this->view($user, $booking);
    }
}
