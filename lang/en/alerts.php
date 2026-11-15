<?php

return [
    'settings' => [
        'announcements' => '📢 Announcements',
        'alerts' => '🔔 Automatic alerts',
        'alerts_description' => 'Messages the bot sends on its own to the users they concern. Each one is off until you switch it on.',
        'cap_hours' => 'Hours between alerts',
        'cap_hours_description' => 'A user gets at most one automatic alert in this many hours, whatever alerts they match. 0 lifts the limit.',
        'send_from_hour' => 'Send from (hour)',
        'send_to_hour' => 'Send until (hour)',
        'send_hours_description' => 'Alerts only go out between these hours (0 to 24, server time), so nobody is messaged at night.',
        'alert_status' => 'Switched on',
        'alert_status_description' => 'Send this alert to the users it concerns.',
        'alert_text' => 'Message',
        'alert_text_description' => "The message, in Telegram HTML. Send - to put the default back.\n\nYou can use: :placeholders\n\nDefault:\n:default",
        'alert_repeat_days' => 'Repeat after (days)',
        'alert_repeat_days_description' => 'Send it again after this many days to a user it still concerns. 0 sends it once only.',
        'unknown_placeholders' => 'Unknown placeholders: :keys',
    ],
    'mute' => [
        'command_description' => 'Turn automatic messages off or on',
        'text' => [
            'muted' => '🔕 Automatic messages are off. You will still get what concerns your own orders and services.',
            'unmuted' => '🔔 Automatic messages are on.',
        ],
        'keys' => [
            'mute' => '🔕 Stop these messages',
            'unmute' => '🔔 Turn them back on',
        ],
        'answers' => [
            'muted' => 'Automatic messages are off.',
            'unmuted' => 'Automatic messages are on.',
        ],
    ],
    'admin' => [
        'keys' => [
            'open' => '🔔 Automatic alerts',
        ],
        'text' => [
            'menu' => "🔔 <b>Automatic alerts</b>\n\nMessages the bot sends on its own. Tap one to get it yourself as a preview.\nTo switch one on or reword it: Settings → Announcements → Automatic alerts.",
            'line' => ":state <b>:label</b>\nSent: :sent · Blocked: :blocked",
            'muted' => '🔕 Users who turned these off: :count',
        ],
    ],
    'web' => [
        'on' => 'On',
        'off' => 'Off',
        'delivery' => ':sent sent · :blocked blocked',
    ],
];
