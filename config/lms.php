<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Kod Pendaftaran Guru
    |--------------------------------------------------------------------------
    |
    | Guru mesti memasukkan kod ini semasa mendaftar akaun. Murid tidak perlu.
    | Tukar kod ini dalam .env jika ia terdedah kepada orang luar.
    |
    */

    'teacher_reg_code' => env('TEACHER_REG_CODE', ''),

    /*
    |--------------------------------------------------------------------------
    | Had Muat Naik (MB)
    |--------------------------------------------------------------------------
    |
    | Had ini mesti lebih rendah daripada `upload_max_filesize` dan `post_max_size`
    | pada server. Untuk rakaman kelas penuh, guru disyorkan guna pautan YouTube.
    |
    */

    'video_max_mb' => (int) env('VIDEO_MAX_MB', 100),
    'material_max_mb' => (int) env('MATERIAL_MAX_MB', 30),
    'quiz_file_max_mb' => (int) env('QUIZ_FILE_MAX_MB', 30),

    /*
    |--------------------------------------------------------------------------
    | Jenis Fail Yang Dibenarkan
    |--------------------------------------------------------------------------
    |
    | Disahkan dengan MIME sebenar (bukan sambungan fail sahaja). Nama fail
    | sentiasa dijana semula sebagai UUID, jadi `nota.php.pdf` tidak boleh berlaku.
    |
    */

    'video_mimes' => ['mp4', 'webm'],
    'video_mimetypes' => ['video/mp4', 'video/webm'],

    'thumbnail_mimes' => ['jpg', 'jpeg', 'png', 'webp'],

    'material_mimes' => ['pdf', 'ppt', 'pptx', 'doc', 'docx', 'xls', 'xlsx', 'png', 'jpg', 'jpeg'],

    'quiz_file_mimes' => ['pdf', 'doc', 'docx'],

    /*
    |--------------------------------------------------------------------------
    | Kuiz
    |--------------------------------------------------------------------------
    */

    'quiz' => [
        'min_options' => 2,
        'max_options' => 6,
        'default_options' => 4,
        'default_points' => 10,
        'max_questions' => 100,
    ],

];
