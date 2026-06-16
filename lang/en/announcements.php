<?php

return [
    'main' => [
        'text' => [
            'menu' => '📢 <b><i>Announcements</i></b>'
                . "\r\n"
                . "\r\nManage your announcements using the options below 👇",
            'menu_empty' => '📢 <b><i>Announcements</i></b>'
                . "\r\n"
                . "\r\nNo announcements yet. Create one using the button below 👇",
            'show' => '📢 <b><i>Announcement</i></b>'
                . "\r\n"
                . "\r\n❔ <b>Label:</b> <i>:label</i>"
                . "\r\n❔ <b>Message:</b>"
                . "\r\n<blockquote expandable>:message</blockquote>"
                . "\r\n❔ <b>Sent at:</b> <i>:sentAt</i>"
                . "\r\n❔ <b>Method:</b> <i>:method</i>"
                . "\r\n"
                . "\r\nYou can manage this announcement using the options below 👇",
            'sendMessagePrompt' => '❓ Send the announcement message:',
            'enterField' => '❓ Enter the new :field:',
            'messageRequiredForHtml' => 'You must set a message to preview in HTML mode.',
            'sendingAnnouncement' => '📤 <b><i>Send Announcement</i></b>'
                . "\r\n"
                . "\r\n❔ <b>Label:</b> <i>:label</i>"
                . "\r\n"
                . "\r\nChoose target users and send/delete status from the list below 👇",
        ],
        'answers' => [
            'menuLoaded' => 'Announcements loaded.',
            'creatingAnnouncement' => 'Creating announcement...',
            'previewSent' => 'Preview message sent.',
            'methodChanged' => 'Method changed to :method.',
            'created' => '✅ Announcement created successfully.',
            'updated' => '✅ Announcement updated successfully.',
            'targetSent' => '✅ Announcement sent to :user.',
            'targetDeleted' => '🗑 Announcement deleted for :user.',
            'targetForbidden' => '⛔ Action failed for :user (forbidden).',
        ],
        'keys' => [
            'create' => 'Create Announcement ➕',
            'columnLabel' => 'Label',
            'columnStatus' => 'Status',
            'notSentYet' => 'Not sent yet ⏳',
            'preview' => 'Preview 👁',
            'send' => 'Send Announcement 📤',
            'changeLabel' => 'Change Label 🏷',
            'method' => 'Method: :method',
            'setMessage' => 'Set Message ✉️',
            'targetStatus' => [
                'pending' => 'Send ➕',
                'sent' => 'Delete 🗑',
                'deleted' => 'Re-send 🔁',
                'forbidden' => 'Tried (Forbidden) ⛔',
            ],
        ],
        'fields' => [
            'label' => 'label',
        ],
        'methods' => [
            'html' => 'HTML',
            'copy' => 'Copy',
            'forward' => 'Forward',
        ],
        'values' => [
            'notSentYet' => 'Not sent yet',
            'noMessage' => 'No message set',
        ],
        'lock-keys' => [
            'creatingAnnouncement' => 'Creating announcement',
            'changingField' => 'Changing :field',
            'settingMessage' => 'Setting announcement message',
        ],
    ],
    'reply_key' => 'Announcements 📢',
];
