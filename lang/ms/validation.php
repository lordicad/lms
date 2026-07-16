<?php

/*
 * Fallback validation messages in Bahasa Melayu.
 *
 * Most user-facing rules carry a hand-written message in their FormRequest; these cover
 * everything else so a stray rule can never surface English text to a child.
 */

return [
    'accepted' => 'Medan :attribute mesti diterima.',
    'active_url' => 'Medan :attribute bukan URL yang sah.',
    'after' => 'Medan :attribute mesti tarikh selepas :date.',
    'alpha' => 'Medan :attribute hanya boleh mengandungi huruf.',
    'alpha_dash' => 'Medan :attribute hanya boleh mengandungi huruf, nombor, sengkang dan garis bawah.',
    'alpha_num' => 'Medan :attribute hanya boleh mengandungi huruf dan nombor.',
    'array' => 'Medan :attribute mesti sebuah senarai.',
    'before' => 'Medan :attribute mesti tarikh sebelum :date.',
    'between' => [
        'array' => 'Medan :attribute mesti mengandungi antara :min dan :max item.',
        'file' => 'Saiz fail :attribute mesti antara :min dan :max kilobait.',
        'numeric' => 'Medan :attribute mesti antara :min dan :max.',
        'string' => 'Medan :attribute mesti antara :min dan :max aksara.',
    ],
    'boolean' => 'Medan :attribute mesti benar atau salah.',
    'confirmed' => 'Pengesahan :attribute tidak sepadan.',
    'current_password' => 'Kata laluan tidak betul.',
    'date' => 'Medan :attribute bukan tarikh yang sah.',
    'different' => 'Medan :attribute dan :other mesti berbeza.',
    'digits' => 'Medan :attribute mesti :digits digit.',
    'email' => 'Medan :attribute mesti alamat emel yang sah.',
    'exists' => 'Pilihan :attribute tidak sah.',
    'file' => 'Medan :attribute mesti sebuah fail.',
    'filled' => 'Medan :attribute mesti diisi.',
    'image' => 'Medan :attribute mesti sebuah imej.',
    'in' => 'Pilihan :attribute tidak sah.',
    'integer' => 'Medan :attribute mesti nombor bulat.',
    'max' => [
        'array' => 'Medan :attribute tidak boleh melebihi :max item.',
        'file' => 'Saiz fail :attribute tidak boleh melebihi :max kilobait.',
        'numeric' => 'Medan :attribute tidak boleh melebihi :max.',
        'string' => 'Medan :attribute tidak boleh melebihi :max aksara.',
    ],
    'mimes' => 'Medan :attribute mesti fail berjenis: :values.',
    'mimetypes' => 'Medan :attribute mesti fail berjenis: :values.',
    'min' => [
        'array' => 'Medan :attribute mesti mengandungi sekurang-kurangnya :min item.',
        'file' => 'Saiz fail :attribute mesti sekurang-kurangnya :min kilobait.',
        'numeric' => 'Medan :attribute mesti sekurang-kurangnya :min.',
        'string' => 'Medan :attribute mesti sekurang-kurangnya :min aksara.',
    ],
    'numeric' => 'Medan :attribute mesti nombor.',
    'regex' => 'Format :attribute tidak sah.',
    'required' => 'Medan :attribute wajib diisi.',
    'required_if' => 'Medan :attribute wajib diisi apabila :other ialah :value.',
    'same' => 'Medan :attribute dan :other mesti sepadan.',
    'string' => 'Medan :attribute mesti teks.',
    'unique' => 'Nilai :attribute sudah digunakan.',
    'uploaded' => 'Fail :attribute gagal dimuat naik. Saiznya mungkin melebihi had server.',
    'url' => 'Format :attribute tidak sah.',

    'custom' => [
        'password' => [
            'min' => 'Kata laluan mesti sekurang-kurangnya :min aksara.',
        ],
    ],

    'attributes' => [
        'name' => 'nama',
        'username' => 'nama pengguna',
        'email' => 'emel',
        'password' => 'kata laluan',
        'password_confirmation' => 'pengesahan kata laluan',
        'grade_level' => 'Tahun',
        'teacher_code' => 'Kod Pendaftaran Guru',
        'title' => 'tajuk',
        'description' => 'penerangan',
        'chapter_id' => 'Bab',
        'subject_id' => 'Subjek',
        'grade_id' => 'Tahun',
        'lesson_id' => 'video',
        'video' => 'video',
        'youtube_url' => 'pautan YouTube',
        'thumbnail' => 'gambar kecil',
        'file' => 'fail',
        'avatar' => 'gambar profil',
        'duration_minutes' => 'masa kuiz',
        'questions' => 'soalan',
        'source' => 'sumber video',
        'type' => 'jenis kuiz',
    ],
];
