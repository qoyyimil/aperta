@php($rp = fn ($n) => \App\Support\Rupiah::format($n))
<x-mail::message>
# Halo, {{ $view['name'] }}

Booking kamu sudah kami terima. Simpan kode berikut untuk melacak status.

<x-mail::panel>
**Kode booking: {{ $view['code'] }}**
</x-mail::panel>

<x-mail::table>
| | |
|:--|--:|
| Layanan | {{ $view['service'] }} |
| Jadwal | {{ $view['date'] }}, {{ $view['time'] }} |
| Lokasi | {{ $view['location'] }} |
| Paket | {{ $view['package'] }} |
@foreach ($view['addons'] as $addon)
| {{ $addon['name'] }} × {{ $addon['qty'] }} | {{ $rp($addon['total']) }} |
@endforeach
@if ($view['price']['discount'])
| Diskon | −{{ $rp($view['price']['discount']) }} |
@endif
| **Total** | **{{ $rp($view['price']['total']) }}** |
</x-mail::table>

@if ($deposit = $view['deposit'])
**Bayar DP minimal {{ $rp($deposit['amount']) }} paling lambat {{ $deposit['dueAt'] }}** ke salah satu rekening berikut:

<x-mail::table>
| Bank | No. rekening | Atas nama |
|:--|:--|:--|
@foreach ($deposit['accounts'] as $account)
| {{ $account['bank'] }} | {{ $account['number'] }} | {{ $account['name'] }} |
@endforeach
</x-mail::table>

Sudah transfer? [Konfirmasi via WhatsApp]({{ $deposit['whatsappUrl'] }}) dan lampirkan bukti transfer.
@else
**Langkah berikutnya:** {{ $view['next'] }}
@endif

<x-mail::button :url="$url">
Lihat status booking
</x-mail::button>

Terima kasih,<br>
{{ config('app.name') }}
</x-mail::message>
