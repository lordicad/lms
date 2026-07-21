<x-mail::message>
# {{ __('Selamat datang ke WeLearn!') }}

@if ($guardianName)
{{ __('Salam sejahtera :name,', ['name' => $guardianName]) }}

{{ __('Akaun WeLearn telah dibuka untuk anak jagaan anda, :name. Berikut ialah butiran log masuknya.', ['name' => $account->name]) }}
@else
{{ __('Salam sejahtera :name,', ['name' => $account->name]) }}

{{ __('Akaun WeLearn anda telah dibuka. Berikut ialah butiran log masuk anda.') }}
@endif

{{-- A teacher signs in with their email; a student with their username. Show whichever applies,
     so nobody is handed an identifier that will not get them in. --}}
<x-mail::panel>
**{{ $account->signsInWithEmail() ? __('Emel') : __('Nama pengguna') }}:** {{ $account->signInIdentifier() }}

**{{ __('Kata laluan sementara') }}:** {{ $plainPassword }}
</x-mail::panel>

@if ($account->signsInWithEmail())
{{ __('Nama paparan anda ialah ":nickname" — itu yang akan dipaparkan di papan pemuka anda.', ['nickname' => $account->username]) }}
@endif

<x-mail::button :url="$loginUrl">
{{ __('Log Masuk') }}
</x-mail::button>

**{{ __('Penting:') }}** {{ __('Kata laluan di atas adalah sementara. Pada log masuk pertama, anda akan diminta menetapkan kata laluan sendiri. Nama pengguna kekal sama.') }}

{{ __('Sila jangan kongsi butiran ini dengan sesiapa.') }}

{{ __('Terima kasih,') }}<br>
{{ config('app.name') }}
</x-mail::message>
