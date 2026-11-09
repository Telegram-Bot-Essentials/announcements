<x-filament-panels::page>
    @php($announcement = $this->announcement())
    <div wire:poll.5s class="grid gap-6">
        <x-filament::section>
            <x-slot name="heading">{{ __('tbe-announcements::web.delivery') }}</x-slot>
            <dl style="display:grid;grid-template-columns:repeat(auto-fit,minmax(8rem,1fr));gap:1rem;">
                @foreach ($this->counts() as $status => $count)
                    <div>
                        <dt style="font-size:.875rem;opacity:.7;">{{ __('tbe-announcements::web.status.'.$status) }}</dt>
                        <dd style="font-size:1.5rem;font-weight:600;">{{ number_format($count) }}</dd>
                    </div>
                @endforeach
            </dl>
            @if ($announcement->sent_at)
                <p style="margin-top:1rem;font-size:.875rem;opacity:.7;">{{ __('tbe-announcements::web.finished_at', ['time' => $announcement->sent_at->diffForHumans()]) }}</p>
            @endif
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">{{ __('tbe-announcements::web.message') }}</x-slot>
            <x-slot name="description">{{ __('tbe-announcements::web.method') }}: {{ $this->methodLabel() }}</x-slot>
            @if ($announcement->method === 'html')
                <pre style="white-space:pre-wrap;font-family:inherit;margin:0;">{{ $announcement->message_text }}</pre>
            @else
                <p style="opacity:.75;">{{ __('tbe-announcements::web.from_telegram') }}</p>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
