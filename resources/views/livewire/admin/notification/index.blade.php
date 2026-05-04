<div>
    <x-admin.page-header title="Notifikasi">
        <x-slot:actions>
            @if($unreadCount > 0)
                <button wire:click="markAllRead"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Tandai Semua Dibaca
                    <span class="rounded-full bg-blue-100 px-1.5 py-0.5 text-xs font-semibold text-blue-700">
                        {{ $unreadCount }}
                    </span>
                </button>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    {{-- Filters --}}
    <div class="mb-4 flex flex-wrap items-center gap-3">
        <div class="flex overflow-hidden rounded-lg border border-gray-300">
            @foreach(['' => 'Semua', 'unread' => 'Belum Dibaca', 'read' => 'Sudah Dibaca'] as $val => $label)
                <button wire:click="$set('readFilter', '{{ $val }}')"
                        class="px-4 py-2 text-sm font-medium {{ $readFilter === $val ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}
                               {{ !$loop->first ? 'border-l border-gray-300' : '' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <select wire:model.live="type"
                class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">Semua Tipe</option>
            @foreach(\App\Enums\NotificationType::cases() as $t)
                <option value="{{ $t->value }}">{{ $t->label() }}</option>
            @endforeach
        </select>
    </div>

    {{-- Notification List --}}
    <div class="space-y-2">
        @forelse($notifications as $notif)
        @php
            $tv = is_object($notif->type) ? $notif->type->value : $notif->type;
            $iconColors = ['info' => 'text-blue-500', 'warning' => 'text-yellow-500', 'error' => 'text-red-500'];
            $bgColors   = ['info' => 'bg-blue-50', 'warning' => 'bg-yellow-50', 'error' => 'bg-red-50'];
        @endphp
        <div wire:key="notif-{{ $notif->id }}"
             wire:click="markRead({{ $notif->id }})"
             class="flex items-start gap-4 rounded-xl bg-white p-4 shadow-sm ring-1 cursor-pointer transition-colors
                    {{ !$notif->is_read ? 'ring-blue-200 bg-blue-50' : 'ring-gray-200 hover:bg-gray-50' }}">

            {{-- Icon --}}
            <div class="flex-shrink-0 rounded-full p-2 {{ $bgColors[$tv] ?? 'bg-gray-100' }}">
                @if($tv === 'info')
                    <svg class="h-5 w-5 {{ $iconColors[$tv] ?? 'text-gray-400' }}" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                    </svg>
                @elseif($tv === 'warning')
                    <svg class="h-5 w-5 {{ $iconColors[$tv] ?? 'text-gray-400' }}" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                @else
                    <svg class="h-5 w-5 {{ $iconColors[$tv] ?? 'text-gray-400' }}" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                @endif
            </div>

            {{-- Content --}}
            <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-sm font-medium text-gray-900 {{ !$notif->is_read ? 'font-semibold' : '' }}">
                        {{ $notif->title }}
                        @if(!$notif->is_read)
                            <span class="ml-2 inline-block h-2 w-2 rounded-full bg-blue-500"></span>
                        @endif
                    </p>
                    <span class="flex-shrink-0 text-xs text-gray-400">
                        {{ $notif->created_at->diffForHumans() }}
                    </span>
                </div>
                <p class="mt-0.5 text-sm text-gray-600">{{ $notif->body }}</p>
            </div>
        </div>
        @empty
        <div class="rounded-xl bg-white p-12 shadow-sm ring-1 ring-gray-200">
            <x-admin.empty-state title="Tidak ada notifikasi"
                description="Belum ada notifikasi{{ $readFilter === 'unread' ? ' yang belum dibaca' : '' }}." />
        </div>
        @endforelse
    </div>

    @if($notifications->hasPages())
        <div class="mt-4">{{ $notifications->links() }}</div>
    @endif
</div>
