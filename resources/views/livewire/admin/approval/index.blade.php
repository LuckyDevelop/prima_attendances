<div>
    <x-admin.page-header title="Approval Center" />

    {{-- Tabs --}}
    <div class="mb-4 flex gap-1 rounded-xl bg-gray-100 p-1 w-fit">
        @foreach(['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'] as $key => $label)
            <button wire:click="$set('tab', '{{ $key }}')"
                    class="rounded-lg px-4 py-2 text-sm font-medium transition-colors
                        {{ $tab === $key ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Filters --}}
    <div class="mb-6 flex flex-wrap items-center gap-3">
        <div class="flex overflow-hidden rounded-lg border border-gray-300">
            <button wire:click="$set('requestType', 'leave')"
                    class="px-4 py-2 text-sm font-medium {{ $requestType === 'leave' ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}">
                Cuti
            </button>
            <button wire:click="$set('requestType', 'permission')"
                    class="border-l border-gray-300 px-4 py-2 text-sm font-medium {{ $requestType === 'permission' ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}">
                Izin
            </button>
        </div>
        <select wire:model.live="departmentId"
                class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">Semua Departemen</option>
            @foreach($departments as $dept)
                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
            @endforeach
        </select>

        @if($tab === 'pending' && !empty($selected))
            <button wire:click="bulkApprove"
                    wire:confirm="Setujui {{ count($selected) }} pengajuan yang dipilih?"
                    class="ml-auto rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">
                Setujui {{ count($selected) }} Terpilih
            </button>
        @endif
    </div>

    {{-- Request List --}}
    <div class="space-y-3">
        @forelse($requests as $req)
        @php
            $reqType = $requestType;
            $sv = is_object($req->status) ? $req->status->value : $req->status;
            $itemKey = $req->id . ':' . $reqType;
        @endphp
        <div wire:key="req-{{ $itemKey }}" class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
            <div class="flex items-start gap-4">
                @if($tab === 'pending')
                    <input type="checkbox" wire:model.live="selected" value="{{ $itemKey }}"
                           class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                @endif

                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <p class="font-semibold text-gray-900">
                                {{ $req->user?->full_name ?? '?' }}
                                <span class="ml-2 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-blue-100 text-blue-700">
                                    {{ $reqType === 'leave' ? 'Cuti' : 'Izin' }}
                                </span>
                            </p>
                            <p class="mt-0.5 text-sm text-gray-500">
                                {{ $req->user?->employee_id }} · {{ $req->user?->department?->name ?? '—' }}
                            </p>
                        </div>
                        @php
                            $colors = ['pending' => 'bg-yellow-100 text-yellow-700', 'approved' => 'bg-green-100 text-green-700', 'rejected' => 'bg-red-100 text-red-700'];
                            $labels = ['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'];
                        @endphp
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $colors[$sv] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ $labels[$sv] ?? ucfirst($sv) }}
                        </span>
                    </div>

                    <div class="mt-2 text-sm text-gray-700">
                        @if($reqType === 'leave')
                            <p>
                                <span class="font-medium">{{ $req->leaveType?->name ?? '—' }}</span>
                                · {{ \Carbon\Carbon::parse($req->start_date)->format('d M') }} – {{ \Carbon\Carbon::parse($req->end_date)->format('d M Y') }}
                                ({{ $req->total_days }} hari)
                            </p>
                        @else
                            <p>
                                <span class="font-medium">{{ ucwords(str_replace('_', ' ', is_object($req->permission_type) ? $req->permission_type->value : $req->permission_type)) }}</span>
                                · {{ \Carbon\Carbon::parse($req->request_date)->format('d M Y') }}
                            </p>
                        @endif
                        <p class="mt-0.5 text-gray-500 text-xs">
                            Diajukan {{ $req->submitted_at ? \Carbon\Carbon::parse($req->submitted_at)->diffForHumans() : '—' }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2 flex-shrink-0">
                    @if($reqType === 'leave')
                        <a href="{{ route('admin.leaves.show', $req->id) }}"
                           class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50">
                            Detail
                        </a>
                    @else
                        <a href="{{ route('admin.permissions.show', $req->id) }}"
                           class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50">
                            Detail
                        </a>
                    @endif
                    @if($tab === 'pending')
                        <button wire:click="openAction({{ $req->id }}, '{{ $reqType }}', 'reject')"
                                class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-100">
                            Tolak
                        </button>
                        <button wire:click="openAction({{ $req->id }}, '{{ $reqType }}', 'approve')"
                                class="rounded-lg bg-green-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-green-700">
                            Setujui
                        </button>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="rounded-xl bg-white p-12 shadow-sm ring-1 ring-gray-200">
            <x-admin.empty-state title="Tidak ada pengajuan"
                description="Tidak ada pengajuan dengan status {{ $tab === 'pending' ? 'menunggu' : $tab }}." />
        </div>
        @endforelse
    </div>

    @if($requests->hasPages())
        <div class="mt-4">
            {{ $requests->links() }}
        </div>
    @endif

    {{-- Action Modal --}}
    <x-ui.modal name="approval-action" max-width="md">
        <x-slot:title>
            {{ $pendingAction === 'approve' ? 'Setujui Pengajuan' : 'Tolak Pengajuan' }}
        </x-slot:title>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Komentar {{ $pendingAction === 'reject' ? '(wajib)' : '(opsional)' }}
            </label>
            <textarea wire:model="comment" rows="3"
                      class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
            @error('comment') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <x-slot:footer>
            <button @click="$dispatch('close-modal', { name: 'approval-action' })"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                Batal
            </button>
            <button wire:click="confirmAction" wire:loading.attr="disabled"
                    class="{{ $pendingAction === 'approve' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700' }} inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium text-white disabled:opacity-60">
                <span wire:loading.remove>{{ $pendingAction === 'approve' ? 'Setujui' : 'Tolak' }}</span>
                <span wire:loading><x-ui.spinner class="h-4 w-4" /> Memproses...</span>
            </button>
        </x-slot:footer>
    </x-ui.modal>
</div>
