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
                . "\r\n📡 <b>روش:</b> <i>:method</i>"
                . "\r\n🕐 <b>زمان ارسال:</b> <i>:sentAt</i>"
                . "\r\n"
                . "\r\n📊 <b>آمار:</b>"
                . "\r\n👥 <b>کل اهداف:</b> <code>:total</code>"
                . "\r\n✅ <b>ارسال شده:</b> <code>:sent</code>"
                . "\r\n⏳ <b>در انتظار:</b> <code>:pending</code>"
                . "\r\n🗑 <b>حذف شده:</b> <code>:deleted</code>"
                . "\r\n⛔ <b>بلاک شده:</b> <code>:forbidden</code>"
                . "\r\n"
                . "\r\nاز دکمه‌های پایین این اعلان رو مدیریت کنید 👇",
            'sendMessagePrompt' => '💬 پیام اعلان رو بفرستید:',
            'enterField' => '✏️ مقدار جدید :field رو وارد کنید:',
            'enterPagePrompt' => '🔢 شماره صفحه را وارد کنید:',
            'messageRequiredForHtml' => '⚠️ برای پیش‌نمایش در حالت HTML باید پیام تنظیم شده باشد.',
            'sendingAnnouncement' => '📤 <b><i>ارسال اعلان</i></b>'
                . "\r\n"
                . "\r\n🏷 <b>برچسب:</b> <i>:label</i>"
                . "\r\n"
                . "\r\nکاربرهای هدف را مدیریت کنید و وضعیت ارسال/حذف را از لیست پایین کنترل کنید 👇",
            'sendingProgressInitial' => '📤 در حال ارسال: 0/:total',
            'deletingProgressInitial' => '🗑 در حال حذف: 0/:total',
            'sendingProgressTemplate' => ':status'
                . "\r\n━━━━━━━━━━━━━━━━━━━━"
                . "\r\n🏷 <b>برچسب:</b> <i>:label</i>"
                . "\r\n📊 <b>پیشرفت:</b> :percent% [<code>:progressBar</code>]"
                . "\r\n"
                . "\r\n👥 <b>کل اهداف:</b> <code>:total</code>"
                . "\r\n✅ <b>ارسال شده:</b> <code>:sent</code>"
                . "\r\n⏳ <b>در انتظار:</b> <code>:pending</code>"
                . "\r\n⛔ <b>بلاک شده:</b> <code>:forbidden</code>"
                . "\r\n━━━━━━━━━━━━━━━━━━━━"
                . "\r\n:footer",
            'deletingProgressTemplate' => ':status'
                . "\r\n━━━━━━━━━━━━━━━━━━━━"
                . "\r\n🏷 <b>برچسب:</b> <i>:label</i>"
                . "\r\n📊 <b>پیشرفت:</b> :percent% [<code>:progressBar</code>]"
                . "\r\n"
                . "\r\n👥 <b>کل اهداف:</b> <code>:total</code>"
                . "\r\n🗑 <b>حذف شده:</b> <code>:deleted</code>"
                . "\r\n⏳ <b>در انتظار:</b> <code>:pending</code>"
                . "\r\n⛔ <b>بلاک شده:</b> <code>:forbidden</code>"
                . "\r\n━━━━━━━━━━━━━━━━━━━━"
                . "\r\n:footer",
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
            'settingPage' => '⏳ در انتظار شماره صفحه...',
            'pageLoaded' => '📄 صفحه :page بارگذاری شد.',
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
            'settingPage' => 'در انتظار شماره صفحه',
        ],
        'status' => [
            'sendingInProgress' => '📤 <b>در حال ارسال اعلان...</b>',
            'sendingCompleted' => '✅ <b>ارسال اعلان با موفقیت به پایان رسید!</b>',
            'sendingFooterProgress' => '⏳ در حال پردازش دسته‌ها، لطفاً شکیبا باشید...',
            'sendingFooterCompleted' => '✨ تمامی پیام‌ها با موفقیت پردازش شدند.',
            'deletingInProgress' => '🗑 <b>در حال حذف اعلان...</b>',
            'deletingCompleted' => '✅ <b>حذف اعلان با موفقیت به پایان رسید!</b>',
            'deletingFooterProgress' => '⏳ در حال حذف پیام‌های ارسال‌شده، لطفاً شکیبا باشید...',
            'deletingFooterCompleted' => '✨ تمامی پیام‌های هدف با موفقیت حذف شدند.',
        ],
        'errors' => [
            'alreadySent' => '⚠️ این اعلان قبلاً برای این کاربر ارسال شده است.',
            'cannotDelete' => '⚠️ امکان حذف اعلان وجود ندارد (ارسال نشده یا قبلاً حذف شده است).',
        ],
    ],
    'reply_key' => '📢 اعلان‌ها',
];
