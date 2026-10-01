<?php

return [
    'main' => [
        'text' => [
            'menu' => '📢 <b><i>Announcements</i></b>'
                ."\r\n"
                ."\r\nUse the buttons below to manage your announcements 👇",
            'menu_empty' => '📢 <b><i>Announcements</i></b>'
                ."\r\n"
                ."\r\nNo announcements yet. Tap the button below to create your first one 👇",
            'show' => '📢 <b><i>Announcement details</i></b>'
                ."\r\n"
                ."\r\n🏷 <b>Label:</b> <i>:label</i>"
                ."\r\n💬 <b>Message:</b>"
                ."\r\n<blockquote expandable>:message</blockquote>"
                ."\r\n📡 <b>Method:</b> <i>:method</i>"
                ."\r\n🕐 <b>Sent at:</b> <i>:sentAt</i>"
                ."\r\n"
                ."\r\n📊 <b>Statistics:</b>"
                ."\r\n👥 <b>Total targets:</b> <code>:total</code>"
                ."\r\n✅ <b>Sent:</b> <code>:sent</code>"
                ."\r\n⏳ <b>Pending:</b> <code>:pending</code>"
                ."\r\n🗑 <b>Deleted:</b> <code>:deleted</code>"
                ."\r\n⛔ <b>Forbidden:</b> <code>:forbidden</code>"
                ."\r\n🚫 <b>Skipped:</b> <code>:skipped</code>"
                ."\r\n⚠️ <b>Failed:</b> <code>:failed</code>"
                ."\r\n"
                ."\r\nUse the buttons below to manage this announcement 👇",
            'sendMessagePrompt' => '💬 Send the announcement message:',
            'enterField' => '✏️ Send the new :field:',
            'enterPagePrompt' => '🔢 Enter the page number:',
            'deleteConfirmation' => 'Are you sure you want to delete the announcement ":label"?',
            'messageRequiredForHtml' => '⚠️ You must set a message before previewing in HTML mode.',
            'sendingAnnouncement' => '📤 <b><i>Send announcement</i></b>'
                ."\r\n"
                ."\r\n🏷 <b>Label:</b> <i>:label</i>"
                ."\r\n"
                ."\r\nUse the list below to manage the target users and their send/delete status 👇",
            'sendingProgressInitial' => '📤 Sending: 0/:total',
            'deletingProgressInitial' => '🗑 Deleting: 0/:total',
            'sendingProgressTemplate' => ':status'
                ."\r\n━━━━━━━━━━━━━━━━━━━━"
                ."\r\n🏷 <b>Label:</b> <i>:label</i>"
                ."\r\n📊 <b>Progress:</b> :percent% [<code>:progressBar</code>]"
                ."\r\n"
                ."\r\n👥 <b>Total targets:</b> <code>:total</code>"
                ."\r\n✅ <b>Sent:</b> <code>:sent</code>"
                ."\r\n⏳ <b>Pending:</b> <code>:pending</code>"
                ."\r\n⛔ <b>Forbidden:</b> <code>:forbidden</code>"
                ."\r\n🚫 <b>Skipped:</b> <code>:skipped</code>"
                ."\r\n⚠️ <b>Failed:</b> <code>:failed</code>"
                ."\r\n━━━━━━━━━━━━━━━━━━━━"
                ."\r\n:footer",
            'deletingProgressTemplate' => ':status'
                ."\r\n━━━━━━━━━━━━━━━━━━━━"
                ."\r\n🏷 <b>Label:</b> <i>:label</i>"
                ."\r\n📊 <b>Progress:</b> :percent% [<code>:progressBar</code>]"
                ."\r\n"
                ."\r\n👥 <b>Total targets:</b> <code>:total</code>"
                ."\r\n🗑 <b>Deleted:</b> <code>:deleted</code>"
                ."\r\n⏳ <b>Pending:</b> <code>:pending</code>"
                ."\r\n⛔ <b>Forbidden:</b> <code>:forbidden</code>"
                ."\r\n━━━━━━━━━━━━━━━━━━━━"
                ."\r\n:footer",
        ],
        'answers' => [
            'menuLoaded' => '📋 Announcements',
            'creatingAnnouncement' => '⏳ Creating the announcement…',
            'previewSent' => '👁 Preview message sent.',
            'methodChanged' => '🔄 Method changed to :method.',
            'created' => '✅ Announcement created.',
            'updated' => '✅ Announcement updated.',
            'deleted' => '🗑 Announcement deleted.',
            'targetSent' => '✅ Announcement sent to :user.',
            'targetDeleted' => '🗑 Announcement deleted for :user.',
            'targetForbidden' => '⛔ Action failed for :user (user blocked the bot).',
            'targetFailed' => '⚠️ Action failed for :user for a temporary reason. Try again.',
            'sendingStarted' => '📤 Sending process started.',
            'deletingStarted' => '🗑 Deleting process started.',
            'settingPage' => '⏳ Waiting for the page number…',
            'pageLoaded' => '📄 Page :page loaded.',
            'sendingProgress' => '📤 Sent: :sent/:total | ⛔ Failed: :forbidden',
            'deletingProgress' => '🗑 Deleted: :deleted/:total | ⛔ Failed: :forbidden',
        ],
        'keys' => [
            'create' => '➕ Create announcement',
            'columnLabel' => '🏷 Label',
            'columnStatus' => '📊 Status',
            'notSentYet' => '⏳ Not sent yet',
            'preview' => '👁 Preview',
            'send' => '📤 Send announcement',
            'changeLabel' => '🏷 Change label',
            'method' => '📡 Method: :method',
            'setMessage' => '✉️ Set message',
            'applyFilters' => '🔍 Apply filters',
            'reloadTargetUsers' => '🔄 Reload target users',
            'startSendingMessages' => '📤 Start Sending',
            'deleteSentMessages' => '🗑 Delete sent messages',
            'targetStatus' => [
                'pending' => '➕ Send',
                'sent' => '🗑 Delete',
                'deleted' => '🔁 Re-send',
                'forbidden' => '⛔ Blocked',
                'skipped' => '🚫 Skipped',
                'failed' => '⚠️ Failed',
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
            'settingPage' => 'Waiting for the page number',
        ],
        'status' => [
            'sendingInProgress' => '📤 <b>Sending announcement…</b>',
            'sendingCompleted' => '✅ <b>Sending completed!</b>',
            'sendingFooterProgress' => '⏳ Processing batches — hang tight…',
            'sendingFooterCompleted' => '✨ All messages have been processed.',
            'deletingInProgress' => '🗑 <b>Deleting announcement…</b>',
            'deletingCompleted' => '✅ <b>Deleting completed!</b>',
            'deletingFooterProgress' => '⏳ Deleting the sent messages — hang tight…',
            'deletingFooterCompleted' => '✨ All target messages have been deleted.',
        ],
        'errors' => [
            'alreadySent' => '⚠️ This user already received the announcement.',
            'cannotDelete' => '⚠️ This announcement can\'t be deleted (it was never sent, or it\'s already deleted).',
        ],
    ],
    'reply_key' => '📢 Announcements',
];
