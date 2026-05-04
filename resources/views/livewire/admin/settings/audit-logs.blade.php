<div>
    <x-admin.page-header title="Audit Log" />

    {{-- Filters --}}
    <div class="mb-4 flex flex-wrap items-center gap-3">
        <div class="relative min-w-[200px]">
            <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                 fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
            </svg>
            <input type="text" wire:model.live.debounce.400ms="userSearch"
                   placeholder="Cari user..."
                   class="w-full rounded-lg border-gray-300 pl-9 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
        </div>

        <select wire:model.live="entity"
                class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">Semua Entity</option>
            @foreach($entityOptions as $opt)
                <option value="{{ $opt }}">{{ ucwords(str_replace('_', ' ', $opt)) }}</option>
            @endforeach
        </select>

        <div class="relative">
            <input type="text" wire:model.live.debounce.400ms="action"
                   placeholder="Filter aksi..."
                   class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 w-36" />
        </div>

        <input type="date" wire:model.live="dateFrom"
               class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
        <input type="date" wire:model.live="dateTo"
               class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />

        @if($entity || $action || $userSearch || $dateFrom || $dateTo)
            <button wire:click="resetFilters"
                    class="text-sm text-gray-500 hover:text-gray-700 underline">Reset</button>
        @endif
    </div>

    {{-- Log Table --}}
    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 w-40">Waktu</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">User</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Aksi</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Entity</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Detail</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 w-28">IP</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($logs as $log)
                <tr wire:key="log-{{ $log->id }}" class="hover:bg-gray-50 text-sm">
                    <td class="px-4 py-3 text-gray-500 text-xs whitespace-nowrap">
                        <p>{{ $log->created_at->format('d M Y') }}</p>
                        <p class="text-gray-400">{{ $log->created_at->format('H:i:s') }}</p>
                    </td>
                    <td class="px-4 py-3">
                        @if($log->user)
                            <p class="font-medium text-gray-900">{{ $log->user->full_name }}</p>
                        @else
                            <span class="text-gray-400 italic">System</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700">
                            {{ $log->action }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-600">
                        @if($log->entity)
                            <span>{{ ucwords(str_replace('_', ' ', $log->entity)) }}</span>
                            @if($log->entity_id)
                                <span class="text-gray-400"> #{{ $log->entity_id }}</span>
                            @endif
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-600 max-w-xs">
                        @if($log->metadata)
                            <details class="cursor-pointer">
                                <summary class="text-xs text-blue-600 hover:underline select-none">Lihat detail</summary>
                                <pre class="mt-1 whitespace-pre-wrap break-all rounded bg-gray-50 p-2 text-xs text-gray-700">{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </details>
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-500 font-mono">
                        {{ $log->ip_address ?? '—' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-12">
                        <x-admin.empty-state title="Tidak ada log" description="Belum ada aktivitas yang tercatat." />
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
        <div class="mt-4">{{ $logs->links() }}</div>
    @endif
</div>
