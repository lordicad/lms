<x-mail::message>
# {{ __('Set semula kata laluan') }}

@if ($guardianName)
{{ __('Salam sejahtera :name,', ['name' => $guardianName]) }}

{{ __('Satu permintaan set semula kata laluan telah dibuat untuk akaun anak jagaan anda, :name. Berikan kod di bawah kepadanya untuk meneruskan.', ['name' => $account->name]) }}
@else
{{ __('Salam sejahtera :name,', ['name' => $account->name]) }}

{{ __('Anda telah memohon untuk set semula kata laluan akaun WeLearn anda. Masukkan kod di bawah pada halaman log masuk untuk meneruskan.') }}
@endif

<x-mail::panel>
**{{ __('Kod pengesahan') }}:** {{ $otp }}
</x-mail::panel>

{{ __('Kod ini sah selama :minutes minit.', ['minutes' => $minutes]) }}

**{{ __('Penting:') }}** {{ __('Selepas kod disahkan, admin sekolah akan menetapkan semula kata laluan dan menghantar butiran log masuk baharu.') }}

{{ __('Jika anda tidak membuat permintaan ini, abaikan e-mel ini — tiada apa-apa yang berubah.') }}

{{ __('Terima kasih,') }}<br>
{{ config('app.name') }}
</x-mail::message>
