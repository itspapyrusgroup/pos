<?php

return [
    'izinkan_stok_negatif' => env('POS_IZINKAN_STOK_NEGATIF', false),
    'void_otp_max_attempts' => (int) env('POS_VOID_OTP_MAX_ATTEMPTS', 5),
    'void_otp_decay_seconds' => (int) env('POS_VOID_OTP_DECAY_SECONDS', 600),
];
