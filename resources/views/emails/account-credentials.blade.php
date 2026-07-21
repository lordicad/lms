<x-mail::message>
# {{ __('Selamat datang ke WeLearn!') }}

@if ($guardianName)
{{ __('Salam sejahtera :name,', ['name' => $guardianName]) }}

{{ __('Akaun WeLearn telah dibuka untuk anak jagaan anda, :name. Berikut ialah butiran log masuknya.', ['name' => $account->name]) }}
@else
{{ __('Salam sejahtera :name,', ['name' => $account->name]) }}

{{ __('Akaun WeLearn anda telah dibuka. Berikut ialah butiran log masuk anda.') }}
@endif

<x-mail::panel>
**{{ __('Nama pengguna') }}:** {{ $account->username }}

**{{ __('Kata laluan sementara') }}:** {{ $plainPassword }}
</x-mail::panel>

<x-mail::button :url="$loginUrl">
{{ __('Log Masuk') }}
</x-mail::button>

**{{ __('Penting:') }}** {{ __('Kata laluan di atas adalah sementara. Pada log masuk pertama, anda akan diminta menetapkan kata laluan sendiri. Nama pengguna kekal sama.') }}

{{ __('Sila jangan kongsi butiran ini dengan sesiapa.') }}

{{ __('Terima kasih,') }}<br>
{{ config('app.name') }}
</x-mail::message>
