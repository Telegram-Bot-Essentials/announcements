<?php

return [
    'main' => [
        'text' => [
            'menu' => '📢 <b><i>اعلان‌ها</i></b>'
                . "\r\n"
                . "\r\nاز دکمه‌های پایین اعلان‌هاتون رو مدیریت کنید 👇",
            'menu_empty' => '📢 <b><i>اعلان‌ها</i></b>'
                . "\r\n"
                . "\r\nهنوز اعلانی ندارید. با دکمه پایین اولین اعلانتون رو بسازید 👇",
            'show' => '📢 <b><i>جزئیات اعلان</i></b>'
                . "\r\n"
                . "\r\n🏷 <b>برچسب:</b> <i>:label</i>"
                . "\r\n💬 <b>پیام:</b>"
                . "\r\n<blockquote expandable>:message</blockquote>"
                . "\r\n🕐 <b>زمان ارسال:</b> <i>:sentAt</i>"
                . "\r\n📡 <b>روش:</b> <i>:method</i>"
                . "\r\n"
                . "\r\nاز دکمه‌های پایین این اعلان رو مدیریت کنید 👇",
            'sendMessagePrompt' => '💬 پیام اعلان رو بفرستید:',
            'enterField' => '✏️ مقدار جدید :field رو وارد کنید:',
            'messageRequiredForHtml' => '⚠️ برای پیش‌نمایش در حالت HTML باید پیام تنظیم شده باشد.',
            'sendingAnnouncement' => '📤 <b><i>ارسال اعلان</i></b>'
                . "\r\n"
                . "\r\n🏷 <b>برچسب:</b> <i>:label</i>"
                . "\r\n"
                . "\r\nکاربرهای هدف را مدیریت کنید و وضعیت ارسال/حذف را از لیست پایین کنترل کنید 👇",
            'sendingProgressInitial' => '📤 در حال ارسال: 0/:total',
            'deletingProgressInitial' => '🗑 در حال حذف: 0/:total',
        ],
        'answers' => [
            'menuLoaded' => '📋 اعلان‌ها بارگذاری شد.',
            'creatingAnnouncement' => '⏳ در حال ساخت اعلان...',
            'previewSent' => '👁 پیام پیش‌نمایش ارسال شد.',
            'methodChanged' => '🔄 روش به :method تغییر کرد.',
            'created' => '✅ اعلان با موفقیت ساخته شد.',
            'updated' => '✅ اعلان با موفقیت به‌روزرسانی شد.',
            'targetSent' => '✅ اعلان برای :user ارسال شد.',
            'targetDeleted' => '🗑 اعلان برای :user حذف شد.',
            'targetForbidden' => '⛔ عملیات برای :user ناموفق بود (ربات بلاک شده).',
            'sendingStarted' => '📤 فرایند ارسال شروع شد.',
            'deletingStarted' => '🗑 فرایند حذف شروع شد.',
            'sendingProgress' => '📤 ارسال‌شده: :sent/:total | ⛔ ناموفق: :forbidden',
            'deletingProgress' => '🗑 حذف‌شده: :deleted/:total | ⛔ ناموفق: :forbidden',
        ],
        'keys' => [
            'create' => '➕ ساخت اعلان',
            'columnLabel' => '🏷 برچسب',
            'columnStatus' => '📊 وضعیت',
            'notSentYet' => '⏳ هنوز ارسال نشده',
            'preview' => '👁 پیش‌نمایش',
            'send' => '📤 ارسال اعلان',
            'changeLabel' => '🏷 تغییر برچسب',
            'method' => '📡 روش: :method',
            'setMessage' => '✉️ تنظیم پیام',
            'applyFilters' => '🔍 اعمال فیلترها',
            'reloadTargetUsers' => '🔄 بازگذاری کاربران هدف',
            'startSendingMessages' => '📤 شروع ارسال',
            'deleteSentMessages' => '🗑 حذف پیام‌های ارسال‌شده',
            'targetStatus' => [
                'pending' => '➕ ارسال',
                'sent' => '🗑 حذف',
                'deleted' => '🔁 ارسال مجدد',
                'forbidden' => '⛔ بلاک شده',
            ],
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
    'reply_key' => '📢 اعلان‌ها',
];
