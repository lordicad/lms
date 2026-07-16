<?php

/*
|--------------------------------------------------------------------------
| Kurikulum Persekolahan 2027 — Senarai Mata Pelajaran Sekolah Rendah
|--------------------------------------------------------------------------
|
| Single source of truth for the primary-school subject taxonomy. If the
| ministry corrects the list, this file is the only edit point: change a row
| here, then rerun `php artisan db:seed --class=Kurikulum2027Seeder --force`.
|
| Row shape: [bil, name, short_name, category, levels, color, icon]
|   - bil        : official numbering; also used as sort_order.
|   - name       : full subject name (never translated). Slug = Str::slug(name).
|   - short_name : compact label for cards/tabs; null falls back to name.
|   - category   : teras | wajib | wajib_mbpk | tambahan | program.
|   - levels     : the Tahun (1..6) this subject is offered in — availability map.
|   - color      : hex accent (--sc). Mid-tone (Tailwind ~600/700) so text-subject
|                  and bg-subject/12 stay AA-legible on both light and dark tokens,
|                  matching the convention the original four subjects already used.
|   - icon       : one emoji, the subject's glyph across cards and headers.
|
| Colours run in hue families per category so the browse page reads as grouped
| sections at a glance: teras = cool jewel tones, wajib = warm, wajib_mbpk =
| pinks, tambahan = greens, program = slate.
*/

return [
    // Mata Pelajaran Teras — cool jewel tones.
    [1,  'Bahasa Melayu',                                          null,                             'teras',      [1, 2, 3, 4, 5, 6], '#2563EB', '📖'],
    [2,  'Bahasa Inggeris',                                        null,                             'teras',      [1, 2, 3, 4, 5, 6], '#4F46E5', '🔤'],
    [3,  'Bahasa Cina (SJK)',                                      null,                             'teras',      [1, 2, 3, 4, 5, 6], '#7C3AED', '🀄'],
    [4,  'Bahasa Tamil (SJK)',                                     null,                             'teras',      [1, 2, 3, 4, 5, 6], '#0891B2', '🪷'],
    [5,  'Matematik',                                              null,                             'teras',      [1, 2, 3, 4, 5, 6], '#0D9488', '🔢'],
    [6,  'Pendidikan Islam',                                       null,                             'teras',      [1, 2, 3, 4, 5, 6], '#0284C7', '🕌'],
    [7,  'Pendidikan Moral',                                       null,                             'teras',      [1, 2, 3, 4, 5, 6], '#9333EA', '🤝'],
    [8,  'Alam dan Manusia: Pembelajaran Bersepadu',               'Alam dan Manusia',               'teras',      [1, 2],             '#0369A1', '🌍'],
    [9,  'Eksplorasi Seni dan Dunia: Pembelajaran Bersepadu',      'Eksplorasi Seni dan Dunia',      'teras',      [3, 4],             '#6D28D9', '🎨'],
    [10, 'Eksplorasi Sains dan Teknologi: Pembelajaran Bersepadu', 'Eksplorasi Sains dan Teknologi', 'teras',      [3, 4],             '#0E7490', '🧭'],
    [11, 'Sejarah',                                                null,                             'teras',      [5, 6],             '#1D4ED8', '📜'],
    [12, 'Sains',                                                  null,                             'teras',      [5, 6],             '#4338CA', '🔬'],

    // Mata Pelajaran Wajib — warm tones.
    [13, 'Pendidikan Jasmani',                                     null,                             'wajib',      [1, 2],             '#EA580C', '🤸'],
    [14, 'Pendidikan Jasmani dan Pendidikan Kesihatan',            'PJPK',                           'wajib',      [3, 4, 5, 6],       '#D97706', '🏃'],
    [15, 'Pendidikan Seni Visual',                                 null,                             'wajib',      [5, 6],             '#DC2626', '🖼️'],
    [16, 'Pendidikan Muzik',                                       null,                             'wajib',      [5, 6],             '#B91C1C', '🎵'],
    [17, 'Teknologi dan Digital',                                  null,                             'wajib',      [5, 6],             '#C2410C', '💻'],

    // Mata Pelajaran Wajib Untuk MBPK (Inklusif) — pinks.
    [18, 'Pendidikan Asas Individu Ketidakupayaan Penglihatan',    'PAIKP',                          'wajib_mbpk', [1, 2, 3, 4, 5, 6], '#DB2777', '🦯'],
    [19, 'Bahasa Isyarat Malaysia',                                null,                             'wajib_mbpk', [1, 2, 3, 4, 5, 6], '#BE185D', '🤟'],
    [20, 'Pengurusan Kehidupan Masalah Pembelajaran',              'PKMP',                           'wajib_mbpk', [1, 2, 3, 4, 5, 6], '#A21CAF', '🧩'],

    // Mata Pelajaran Tambahan — greens.
    [21, 'Bahasa Cina SK',                                         null,                             'tambahan',   [1, 2, 3, 4, 5, 6], '#16A34A', '📕'],
    [22, 'Bahasa Tamil SK',                                        null,                             'tambahan',   [1, 2, 3, 4, 5, 6], '#059669', '📗'],
    [23, 'Bahasa Iban',                                            null,                             'tambahan',   [1, 2, 3, 4, 5, 6], '#65A30D', '🌿'],
    [24, 'Bahasa Kadazandusun',                                    null,                             'tambahan',   [1, 2, 3, 4, 5, 6], '#15803D', '🌾'],
    [25, 'Bahasa Semai',                                           null,                             'tambahan',   [1, 2, 3, 4, 5, 6], '#047857', '🍃'],
    [26, 'Bahasa Arab',                                            null,                             'tambahan',   [1, 2, 3, 4, 5, 6], '#4D7C0F', '🪶'],

    // Program.
    [28, 'Pembentukan Karakter',                                   null,                             'program',    [1, 2, 3, 4, 5, 6], '#475569', '🌟'],
];
