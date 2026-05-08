<?php

return [
    'deposit_intervals' => [
        '3_days' => 3,
        '7_days' => 7,
        '14_days' => 14,
    ],

    'abandonment_intervals' => [
        '24_hours' => 24,
        '48_hours' => 48,
        '7_days' => 168,
    ],

    'abandonment_escalation_channels' => [
        '24_hours' => ['sms'],
        '48_hours' => ['sms', 'email'],
        '7_days' => ['sms', 'email', 'whatsapp', 'admin_alert'],
    ],

    'deposit_escalation_channels' => [
        '3_days' => ['sms'],
        '7_days' => ['sms', 'email'],
        '14_days' => ['sms', 'email', 'admin_alert'],
    ],
];
