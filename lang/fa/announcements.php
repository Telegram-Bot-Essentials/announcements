<?php

return [
    'main' => [
        'text' => [
            'menu' => '📢 <b><i>اعلان‌ها</i></b>'
                . "\r\n"
                . "\r\nاز دکمه‌های پایین اعلان‌هاتون رو مدیریت کنید 👇",
            'menu_empty' => '📢 <b><i>اعلان‌ها</i></b>'
                . "\r\n"
                . "\r\nهنوز اعلانی ندارید. با دکمه پایین یکی بسازید 👇",
            'show' => '📢 <b><i>اعلان</i></b>'
                . "\r\n"
                . "\r\n❔ <b>برچسب:</b> <i>:label</i>"
                . "\r\n❔ <b>پیام:</b>"
                . "\r\n<blockquote expandable>:message</blockquote>"
                . "\r\n❔ <b>زمان ارسال:</b> <i>:sentAt</i>"
                . "\r\n❔ <b>روش:</b> <i>:method</i>"
                . "\r\n"
                . "\r\nاز دکمه‌های پایین این اعلان رو مدیریت کنید 👇",
            'sendMessagePrompt' => '❓ پیام اعلان رو بفرستید:',
            'enterField' => '❓ مقدار جدید :field رو وارد کنید:',
            'messageRequiredForHtml' => 'برای پیش‌نمایش در حالت HTML باید پیام تنظیم شده باشد.',
        ],
        'answers' => [
            'menuLoaded' => 'اعلان‌ها بارگذاری شد.',
            'creatingAnnouncement' => 'در حال ساخت اعلان...',
            'previewSent' => 'پیام پیش‌نمایش ارسال شد.',
            'methodChanged' => 'روش به :method تغییر کرد.',
            'created' => '✅ اعلان با موفقیت ساخته شد.',
            'updated' => '✅ اعلان با موفقیت به‌روزرسانی شد.',
        ],
        'keys' => [
            'create' => '➕ ساخت اعلان',
            'columnLabel' => 'برچسب',
            'columnStatus' => 'وضعیت',
            'notSentYet' => 'هنوز ارسال نشده ⏳',
            'preview' => 'پیش‌نمایش 👁',
            'send' => 'ارسال اعلان 📤',
            'changeLabel' => 'تغییر برچسب 🏷',
            'method' => 'روش: :method',
            'setMessage' => 'تنظیم پیام ✉️',
        ],
        'fields' => [
            'label' => 'برچسب',
        ],
        'methods' => [
            'html' => 'HTML',
            'copy' => 'کپی',
            'forward' => 'فوروارد',
        ],
        'values' => [
            'notSentYet' => 'هنوز ارسال نشده',
            'noMessage' => 'پیامی تنظیم نشده',
        ],
        'lock-keys' => [
            'creatingAnnouncement' => 'در حال ساخت اعلان',
            'changingField' => 'در حال تغییر :field',
            'settingMessage' => 'در حال تنظیم پیام اعلان',
        ],
    ],
    'reply_key' => 'اعلان‌ها 📢',
];
