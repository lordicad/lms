<x-student-layout :title="__('Subjek')">
    @php
        // The prototype cycles this 6-gradient palette across every tile, in order.
        $grads = [
            'linear-gradient(135deg,#DCEAF8,#C3D9F1)',
            'linear-gradient(135deg,#E6E0F6,#D6C9EE)',
            'linear-gradient(135deg,#FBE0EA,#F5C7D8)',
            'linear-gradient(135deg,#D8F0EA,#BCE4D9)',
            'linear-gradient(135deg,#FDEFC8,#FBDF9A)',
            'linear-gradient(135deg,#FBE2DC,#F6C8BE)',
        ];
        $gi = 0;
    @endphp

    {{-- Night mode: swap each pale tile for a dark, same-hue gradient (inline bg wins by default,
         so these overrides use !important). One rule per palette index. --}}
    <style>
        html.theme-dark .sc-0 { background:linear-gradient(135deg,#2E425C,#25344B) !important; box-shadow:0 0 22px rgba(96,140,200,.22), 0 6px 18px rgba(0,0,0,.35) !important; }
        html.theme-dark .sc-1 { background:linear-gradient(135deg,#3E3768,#332C57) !important; box-shadow:0 0 22px rgba(146,126,214,.22), 0 6px 18px rgba(0,0,0,.35) !important; }
        html.theme-dark .sc-2 { background:linear-gradient(135deg,#5A3A4C,#48303E) !important; box-shadow:0 0 22px rgba(206,124,156,.22), 0 6px 18px rgba(0,0,0,.35) !important; }
        html.theme-dark .sc-3 { background:linear-gradient(135deg,#265042,#1F4237) !important; box-shadow:0 0 22px rgba(78,186,150,.22), 0 6px 18px rgba(0,0,0,.35) !important; }
        html.theme-dark .sc-4 { background:linear-gradient(135deg,#5A4E2E,#484023) !important; box-shadow:0 0 22px rgba(206,176,92,.22), 0 6px 18px rgba(0,0,0,.35) !important; }
        html.theme-dark .sc-5 { background:linear-gradient(135deg,#5A4238,#48342C) !important; box-shadow:0 0 22px rgba(214,150,120,.22), 0 6px 18px rgba(0,0,0,.35) !important; }
        /* A touch stronger on hover (the card also lifts via .wl-lift). */
        html.theme-dark .sc-0:hover { box-shadow:0 0 30px rgba(96,140,200,.34), 0 12px 26px rgba(0,0,0,.4) !important; }
        html.theme-dark .sc-1:hover { box-shadow:0 0 30px rgba(146,126,214,.34), 0 12px 26px rgba(0,0,0,.4) !important; }
        html.theme-dark .sc-2:hover { box-shadow:0 0 30px rgba(206,124,156,.34), 0 12px 26px rgba(0,0,0,.4) !important; }
        html.theme-dark .sc-3:hover { box-shadow:0 0 30px rgba(78,186,150,.34), 0 12px 26px rgba(0,0,0,.4) !important; }
        html.theme-dark .sc-4:hover { box-shadow:0 0 30px rgba(206,176,92,.34), 0 12px 26px rgba(0,0,0,.4) !important; }
        html.theme-dark .sc-5:hover { box-shadow:0 0 30px rgba(214,150,120,.34), 0 12px 26px rgba(0,0,0,.4) !important; }
    </style>

    @if ($grade && $subjectsByCategory->isNotEmpty())
        <div style="display:flex;flex-direction:column;gap:18px">
            <div style="display:flex;align-items:flex-start;gap:16px">
                <span class="hi-tile" style="width:48px;height:48px;border-radius:14px;background:#DCF2EE;color:#0F7A68;display:grid;place-items:center;flex-shrink:0"><x-icon name="book" style="width:24px;height:24px" /></span>
                <div style="display:flex;flex-direction:column;gap:2px;min-width:0">
                    <h2 style="margin:0;font-family:'Geist',sans-serif;font-size:22px;font-weight:800;letter-spacing:-.01em;color:var(--wl-ink)">{{ __('Subjek') }}</h2>
                    <span style="font-size:14px;font-weight:600;color:var(--wl-muted)">{{ $grade->name }} · {{ __('Pilih subjek untuk melihat bab dan video') }}</span>
                </div>
            </div>

            @foreach (\App\Models\Subject::CATEGORIES as $category)
                @php($group = $subjectsByCategory[$category] ?? collect())
                @if ($group->isNotEmpty())
                    <div style="display:flex;flex-direction:column;gap:14px;margin-bottom:10px">
                        <span style="font-family:'Geist',sans-serif;font-size:13px;font-weight:800;letter-spacing:.14em;text-transform:uppercase;color:var(--wl-muted-2)">{{ \App\Models\Subject::categoryLabel($category) }}</span>
                        <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px">
                            @foreach ($group as $subject)
                                @php($gidx = $gi++ % count($grads))
                                <a href="{{ route('belajar.subjek', ['subject' => $subject->slug, 'grade' => $grade->level]) }}" class="wl-lift sc-{{ $gidx }}"
                                   style="background:{{ $grads[$gidx] }};border:1px solid var(--wl-line);border-radius:18px;padding:20px;min-height:160px;display:flex;flex-direction:column;box-shadow:0 4px 16px var(--wl-line);cursor:pointer;text-decoration:none">
                                    <x-subject-icon :subject="$subject" :size="26" />
                                    <div style="margin-top:auto;margin-bottom:12px;display:flex;flex-direction:column;gap:3px">
                                        <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:16px;color:var(--wl-ink)">{{ $subject->displayName() }}</span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    @else
        <div style="background:var(--wl-surface);border:1px dashed var(--wl-line-3);border-radius:22px;padding:56px;display:flex;flex-direction:column;align-items:center;gap:10px;text-align:center;max-width:520px;margin:0 auto">
            <span style="font-size:32px">🧭</span>
            <h3 style="margin:0;font-family:'Geist',sans-serif;font-size:19px;font-weight:800;color:var(--wl-ink)">{{ __('Tiada subjek') }}</h3>
            <p style="margin:0;font-size:14.5px;color:var(--wl-muted);max-width:360px">{{ __('Sila kemas kini profil anda dan pilih Tahun.') }}</p>
            <a href="{{ route('profile.edit') }}" class="wl-btn-primary" style="margin-top:6px;min-height:46px;display:inline-flex;align-items:center;border-radius:12px;background:#17907B;color:#fff;font-family:'Geist',sans-serif;font-weight:800;font-size:14.5px;padding:0 22px;text-decoration:none">{{ __('Kemas Kini Profil') }}</a>
        </div>
    @endif
</x-student-layout>
