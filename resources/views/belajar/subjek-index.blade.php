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

    @if ($grade && $subjectsByCategory->isNotEmpty())
        <div style="display:flex;flex-direction:column;gap:18px">
            <div style="display:flex;align-items:baseline;gap:12px;flex-wrap:wrap">
                <h2 style="margin:0;font-family:'Geist',sans-serif;font-size:22px;font-weight:800;color:#28293F">{{ __('Subjek — :grade', ['grade' => $grade->name]) }}</h2>
                <span style="font-size:14px;color:#8B8AA3">{{ __('Pilih subjek untuk melihat bab dan video') }}</span>
            </div>

            @foreach (\App\Models\Subject::CATEGORIES as $category)
                @php($group = $subjectsByCategory[$category] ?? collect())
                @if ($group->isNotEmpty())
                    <div style="display:flex;flex-direction:column;gap:14px;margin-bottom:10px">
                        <span style="font-family:'Geist',sans-serif;font-size:13px;font-weight:800;letter-spacing:.14em;text-transform:uppercase;color:#6C6F87">{{ \App\Models\Subject::categoryLabel($category) }}</span>
                        <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px">
                            @foreach ($group as $subject)
                                @php($grad = $grads[$gi++ % count($grads)])
                                <a href="{{ route('belajar.subjek', ['subject' => $subject->slug, 'grade' => $grade->level]) }}" class="wl-lift"
                                   style="background:{{ $grad }};border:1px solid rgba(46,44,80,.05);border-radius:18px;padding:20px;min-height:160px;display:flex;flex-direction:column;box-shadow:0 4px 16px rgba(46,44,80,.05);cursor:pointer;text-decoration:none">
                                    <x-subject-emoji :subject="$subject" style="font-size:24px" />
                                    <div style="margin-top:auto;display:flex;flex-direction:column;gap:3px">
                                        <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:16px;color:#28293F">{{ $subject->displayName() }}</span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    @else
        <div style="background:#fff;border:1px dashed rgba(46,44,80,.2);border-radius:22px;padding:56px;display:flex;flex-direction:column;align-items:center;gap:10px;text-align:center;max-width:520px;margin:0 auto">
            <span style="font-size:32px">🧭</span>
            <h3 style="margin:0;font-family:'Geist',sans-serif;font-size:19px;font-weight:800;color:#28293F">{{ __('Tiada subjek') }}</h3>
            <p style="margin:0;font-size:14.5px;color:#8B8AA3;max-width:360px">{{ __('Sila kemas kini profil anda dan pilih Tahun.') }}</p>
            <a href="{{ route('profile.edit') }}" class="wl-btn-primary" style="margin-top:6px;min-height:46px;display:inline-flex;align-items:center;border-radius:12px;background:#17907B;color:#fff;font-family:'Geist',sans-serif;font-weight:800;font-size:14.5px;padding:0 22px;text-decoration:none">{{ __('Kemas Kini Profil') }}</a>
        </div>
    @endif
</x-student-layout>
