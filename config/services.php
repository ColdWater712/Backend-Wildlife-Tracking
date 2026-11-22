<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Groq (vision, text, dan Whisper untuk transkripsi audio)
    'groq' => [
        'key' => env('GROQ_API_KEY', ''),
        'vision_model' => env('GROQ_VISION_MODEL', 'qwen/qwen3.8-27b'),
        'text_model' => env('GROQ_TEXT_MODEL', 'qwen/qwen3.8-27b'),
        'audio_model' => env('GROQ_AUDIO_MODEL', 'whisper-large-v3-turbo'),
        'timeout' => (int) env('GROQ_TIMEOUT', 60),
    ],

    // Opsional: microservice klasifikasi audio (YAMNet / BirdNET / PANNs, dsb.)
    // Kontrak: POST {url}/classify-audio (multipart "file") -> {label, confidence, category?, species?}
    'ai_audio' => [
        'url' => env('AI_AUDIO_SERVICE_URL'),
        'model' => env('AI_AUDIO_SERVICE_MODEL', 'audio-classifier'),
        'timeout' => (int) env('AI_AUDIO_SERVICE_TIMEOUT', 60),
    ],

    // Dipakai untuk mengambil frame dari video kamera jebak
    'ffmpeg' => [
        'path' => env('FFMPEG_PATH', 'ffmpeg'),
    ],

];