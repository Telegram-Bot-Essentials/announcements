<x-filament-panels::page>
    @php($announcement = $this->announcement())
    <div class="grid gap-6">
        @if ($announcement->sent_at)
            <p style="font-size:.875rem;opacity:.7;margin:0;" title="{{ \TelegramBotEssentials\Essence\Web\PanelCalendar::format($announcement->sent_at) }}">{{ __('tbe-announcements::web.finished_at', ['time' => $announcement->sent_at->diffForHumans()]) }}</p>
        @endif

        <x-filament::section>
            <x-slot name="heading">{{ __('tbe-announcements::web.message') }}</x-slot>
            <x-slot name="description">{{ __('tbe-announcements::web.method') }}: {{ $this->methodLabel() }}</x-slot>
            @if ($announcement->method === 'html')
                <pre style="white-space:pre-wrap;font-family:inherit;margin:0;">{{ $announcement->message_text }}</pre>
            @else
                <x-tbe::empty>{{ __('tbe-announcements::web.from_telegram') }}</x-tbe::empty>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
