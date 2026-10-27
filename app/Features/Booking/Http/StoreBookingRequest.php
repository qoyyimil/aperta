<?php

namespace App\Features\Booking\Http;

use App\Models\Client;
use App\Support\Turnstile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StoreBookingRequest extends FormRequest
{
    /** "+62 812-3456-7890" dan "0812 3456 7890" dianggap nomor yang sama. */
    protected function prepareForValidation(): void
    {
        $this->merge(['whatsapp' => Client::normalizePhone((string) $this->whatsapp)]);
    }

    public function rules(): array
    {
        $active = fn(string $table) => Rule::exists($table, 'id')->where('is_active', true);

        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'whatsapp' => ['required', 'regex:/^08\d{8,13}$/'],
            'instagram' => ['nullable', 'string', 'max:60'],
            'city' => ['required', Rule::in(config('aperta.cities'))],
            'location' => ['nullable', 'string', 'max:255'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'institution' => ['nullable', 'string', 'max:150'],
            'package_id' => ['required', $active('packages')],
            'session_date' => ['required', 'date_format:Y-m-d'],
            'starts_at' => ['required', 'date_format:H:i'],
            'addons' => ['array'],
            'addons.*.id' => ['required', 'distinct', $active('addons')],
            'addons.*.qty' => ['required', 'integer', 'min:1'],
            'voucher' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'consent' => ['accepted'],
        ];
    }

    /** Captcha dicek setelah isian valid, agar tokennya tidak terbuang. */
    protected function passedValidation(): void
    {
        if (!Turnstile::passes($this->string('captcha')->value(), $this->ip())) {
            throw ValidationException::withMessages(['captcha' => 'Verifikasi gagal. Centang ulang lalu kirim lagi.']);
        }
    }

    public function messages(): array
    {
        return [
            'whatsapp.regex' => 'Gunakan nomor WhatsApp Indonesia, mis. 081234567890.',
            'consent.accepted' => 'Setujui kebijakan privasi dan syarat layanan untuk melanjutkan.',
            'starts_at.required' => 'Pilih jam sesi.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama lengkap',
            'whatsapp' => 'nomor WhatsApp',
            'city' => 'kota',
            'package_id' => 'paket',
            'session_date' => 'tanggal sesi',
            'starts_at' => 'jam sesi',
            'institution' => 'kampus / instansi',
            'notes' => 'catatan',
        ];
    }
}
