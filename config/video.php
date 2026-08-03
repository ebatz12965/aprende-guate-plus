<?php

return [
    'ffmpeg_binary' => env('VIDEO_FFMPEG_BINARY', 'ffmpeg'),

    'qualities' => [
        ['name' => '360p', 'height' => 360, 'video_bitrate' => '800k', 'audio_bitrate' => '96k'],
        ['name' => '480p', 'height' => 480, 'video_bitrate' => '1400k', 'audio_bitrate' => '128k'],
        ['name' => '720p', 'height' => 720, 'video_bitrate' => '2800k', 'audio_bitrate' => '128k'],
    ],
];
