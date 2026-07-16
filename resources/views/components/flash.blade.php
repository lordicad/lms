{{-- Session feedback. Rendered once, at the top of the page body. --}}

@if (session('status'))
    <x-alert type="success" class="mb-6">{{ session('status') }}</x-alert>
@endif

@if (session('error'))
    <x-alert type="danger" class="mb-6">{{ session('error') }}</x-alert>
@endif

@if (session('info'))
    <x-alert type="warn" class="mb-6">{{ session('info') }}</x-alert>
@endif

@if ($errors->any() && $errors->count() > 3)
    <x-alert type="danger" class="mb-6">
        {{ __('Ada :count ralat pada borang. Sila semak medan yang bertanda merah di bawah.', ['count' => $errors->count()]) }}
    </x-alert>
@endif
