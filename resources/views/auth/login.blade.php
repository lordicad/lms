{{-- tabs off: with registration hidden, the lone "Log Masuk" tab was redundant. Remove :tabs to bring it back. --}}
<x-welearn-auth active="login" :tabs="false" :title="__('Log Masuk')">
    <div class="wla-stack">
        <div class="wla-head">
            <h2>{{ __('Selamat kembali!') }}</h2>
            <p>{{ __('Murid boleh log masuk dengan nama pengguna sahaja.') }}</p>
        </div>

        @if (session('status'))
            <div class="wla-alert info">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="wla-stack">
            @csrf

            <label for="login" class="wla-label">
                {{ __('Nama pengguna atau emel') }}
                <input id="login" name="login" type="text" value="{{ old('login') }}"
                       required autofocus autocomplete="username" class="wla-input"
                       placeholder="cth: aiman123" @error('login') aria-invalid="true" @enderror>
            </label>
            @error('login')
                <p class="wla-field-error">{{ $message }}</p>
            @enderror

            <label for="password" class="wla-label">
                <span class="wla-label-row">
                    {{ __('Kata laluan') }}
                    <a href="#fp" id="fp-toggle" style="font-size:13px;font-weight:700">{{ __('Lupa kata laluan?') }}</a>
                </span>
                <input id="password" name="password" type="password" required autocomplete="current-password"
                       class="wla-input" placeholder="••••••••" @error('password') aria-invalid="true" @enderror>
            </label>
            @error('password')
                <p class="wla-field-error">{{ $message }}</p>
            @enderror

            <label for="remember" style="display:flex;align-items:center;gap:10px;font-weight:700;font-size:14px;color:var(--muted);cursor:pointer">
                <input id="remember" name="remember" type="checkbox" style="width:18px;height:18px;accent-color:var(--brand)">
                {{ __('Ingat saya') }}
            </label>

            <button type="submit" class="wla-btn">{{ __('Log Masuk') }}</button>
        </form>

        {{-- Forgot-password (OTP) — plain JS, since the auth shell deliberately ships no Alpine.
             Reveals under the login button; a code goes to the account's email (a teacher's own,
             a student's guardian's), and a correct code raises a request in the admin's bell. --}}
        {{-- .wla-stack sets display:flex, which beats the browser's [hidden] rule — so restore it
             here or every step would show at once. --}}
        <style>#fp[hidden], #fp [hidden] { display: none !important; }</style>
        <div id="fp" class="wla-stack" hidden>
            <div style="height:1px;background:var(--line)"></div>
            <div class="wla-head">
                <h2 style="font-size:19px">{{ __('Set semula kata laluan') }}</h2>
                <p>{{ __('Masukkan nama pengguna atau emel anda. Untuk murid, kod dihantar ke emel penjaga.') }}</p>
            </div>

            <div id="fp-alert" class="wla-alert err" hidden></div>

            {{-- Step 1: request a code --}}
            <div id="fp-step-request" class="wla-stack">
                <label for="fp-login" class="wla-label">
                    {{ __('Nama pengguna atau emel') }}
                    <input id="fp-login" type="text" class="wla-input" placeholder="cth: aiman123" autocomplete="username">
                </label>
                <button type="button" id="fp-send" class="wla-btn">{{ __('Hantar Kod') }}</button>
            </div>

            {{-- Step 2: enter the code --}}
            <div id="fp-step-verify" class="wla-stack" hidden>
                <p id="fp-hint" class="wla-hint"></p>
                <label for="fp-otp" class="wla-label">
                    {{ __('Kod pengesahan') }}
                    <input id="fp-otp" type="text" inputmode="numeric" maxlength="6" class="wla-input" placeholder="000000" autocomplete="one-time-code">
                </label>
                <button type="button" id="fp-verify" class="wla-btn">{{ __('Sahkan Kod') }}</button>
                <button type="button" id="fp-resend" class="wla-back" style="background:none;border:none;cursor:pointer;text-align:left;padding:0;color:var(--brand)">{{ __('Hantar semula kod') }}</button>
            </div>

            {{-- Step 3: done --}}
            <div id="fp-step-done" hidden>
                <div class="wla-alert info">{{ __('Pengesahan berjaya. Admin sekolah anda telah dimaklumkan dan akan menetapkan semula kata laluan anda. Butiran log masuk baharu akan dihantar ke emel.') }}</div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const token = '{{ csrf_token() }}';
            const byId = (id) => document.getElementById(id);
            const wrap = byId('fp'), alertBox = byId('fp-alert');
            const stepRequest = byId('fp-step-request'), stepVerify = byId('fp-step-verify'), stepDone = byId('fp-step-done');

            const showAlert = (msg) => { alertBox.textContent = msg || ''; alertBox.hidden = ! msg; };

            byId('fp-toggle').addEventListener('click', (e) => {
                e.preventDefault();
                wrap.hidden = ! wrap.hidden;
                if (! wrap.hidden) {
                    byId('fp-login').value = byId('login').value;
                    byId('fp-login').focus();
                    wrap.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            });

            async function post(url, data, btn) {
                showAlert('');
                if (btn) btn.disabled = true;
                try {
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                        body: JSON.stringify(data),
                    });
                    const body = await res.json().catch(() => ({}));
                    return { ok: res.ok && body.ok, body };
                } catch (_) {
                    return { ok: false, body: { message: '{{ __('Ralat rangkaian. Sila cuba lagi.') }}' } };
                } finally {
                    if (btn) btn.disabled = false;
                }
            }

            async function send(btn) {
                const { ok, body } = await post('{{ route('password.otp.send') }}', { login: byId('fp-login').value }, btn);
                if (ok) {
                    byId('fp-hint').textContent = body.hint || '';
                    stepRequest.hidden = true;
                    stepVerify.hidden = false;
                    byId('fp-otp').focus();
                } else {
                    showAlert(body.message || '{{ __('Ralat. Sila cuba lagi.') }}');
                }
            }

            byId('fp-send').addEventListener('click', () => send(byId('fp-send')));
            byId('fp-resend').addEventListener('click', () => send(byId('fp-resend')));

            byId('fp-verify').addEventListener('click', async () => {
                const { ok, body } = await post('{{ route('password.otp.verify') }}', { login: byId('fp-login').value, otp: byId('fp-otp').value }, byId('fp-verify'));
                if (ok) {
                    stepVerify.hidden = true;
                    stepDone.hidden = false;
                } else {
                    showAlert(body.message || '{{ __('Kod tidak sah.') }}');
                }
            });
        })();
    </script>
</x-welearn-auth>
