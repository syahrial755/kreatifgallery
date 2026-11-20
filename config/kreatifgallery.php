<?php

/*
 * Aturan bisnis yang bersumber dari diagram (Sequence "Mengunggah Karya" & Activity "Publikasi Karya").
 * Dipusatkan di sini supaya validasi, UI, dan test memakai angka yang sama.
 */
return [
    'upload' => [
        'min_files' => 1,
        'max_files' => 10,
        'max_size_kb' => 20 * 1024, // 20 MB per file
        'max_title' => 150,
        'throttle_per_minute' => 6, // throttle:unggah
        'image_mimes' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
        'video_mimes' => ['mp4', 'webm'],
        'mime_types' => [
            'image/jpeg', 'image/png', 'image/webp', 'image/gif',
            'video/mp4', 'video/webm',
        ],
        'thumb_width' => 720,
        'disk' => 'public',
        'dir' => 'karya',
    ],

    'status' => [
        'menunggu' => 'menunggu',
        'disetujui' => 'disetujui',
        'ditolak' => 'ditolak',
    ],
];
