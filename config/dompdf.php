<?php

// Nur die Abweichungen vom Paket-Standard; Rest kommt aus vendor/barryvdh/laravel-dompdf/config/dompdf.php.
return [
    'show_warnings' => false,
    'options' => [
        'font_dir' => storage_path('fonts'),
        'font_cache' => storage_path('fonts'),
        'temp_dir' => sys_get_temp_dir(),
        'chroot' => realpath(base_path()),
        'enable_font_subsetting' => true,
        'pdf_backend' => 'CPDF',
        'default_media_type' => 'print',
        'default_paper_size' => 'a4',
        'default_paper_orientation' => 'portrait',
        'default_font' => 'DejaVu Sans',
        'dpi' => 96,
        'enable_php' => false,
        'enable_javascript' => false,
        'enable_remote' => false,
        'allowed_protocols' => ['data://' => ['rules' => []], 'file://' => ['rules' => []]],
        'font_height_ratio' => 1.1,
        'enable_html5_parser' => true,
    ],
];
