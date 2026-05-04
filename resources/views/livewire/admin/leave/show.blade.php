<div>
    <x-admin.page-header title="Detail Cuti #{{ $leave->id }}">
        <x-slot:actions>
            <a href="{{ route('admin.leaves.index') }}"
               class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                ← Kembali
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="mx-auto max-w-3xl space-y-6">

        {{-- Pemohon --}}
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500">Pemohon</h3>
            <div class="flex items-center gap-4">
                <div class="h-14 w-14 flex-shrink-0 overflow-hidden rounded-full bg-gray-200">
                    @if($leave->user?->photo)
                        <img src="{{ Storage::url($leave->user->photo) }}" class="h-full w-full object-cover" alt="" />
                    @else
                        <span class="flex h-full w-full items-center justify-center text-xl font-semibold text-gray-500">
                            {{ strtoupper(substr($leave->user?->full_name ?? '?', 0, 1)) }}
                        </span>
                    @endif
                </div>
                <div>
                    <p class="font-semibold text-gray-900">{{ $leave->user?->full_name ?? '—' }}</p>
                    <p class="text-sm text-gray-500">{{ $leave->user?->employee_id }} · {{ $leave->user?->department?->name ?? '—' }}</p>
                    <p class="text-sm text-gray-500">{{ ucfirst($leave->user?->role?->value ?? '—') }}</p>
                </div>
            </div>
        </div>

        {{-- Detail Pengajuan --}}
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500">Detail Pengajuan</h3>
            <dl class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-gray-500">Jenis Cuti</dt>
                    <dd class="font-medium text-gray-900">{{ $leave->leaveType?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Total Hari</dt>
                    <dd class="font-medium text-gray-900">{{ $leave->total_days }} hari</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Tanggal Mulai</dt>
                    <dd class="font-medium text-gray-900">{{ \Carbon\Carbon::parse($leave->start_date)->isoFormat('D MMMM YYYY') }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Tanggal Selesai</dt>
                    <dd class="font-medium text-gray-900">{{ \Carbon\Carbon::parse($leave->end_date)->isoFormat('D MMMM YYYY') }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Tgl Pengajuan</dt>
                    <dd class="font-medium text-gray-900">
                        {{ $leave->submitted_at ? \Carbon\Carbon::parse($leave->submitted_at)->isoFormat('D MMMM YYYY, HH:mm') : '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500">Status</dt>
                    <dd>
                        @php
                            $sv = is_object($leave->status) ? $leave->status->value : $leave->status;
                            $colors = ['pending' => 'bg-yellow-100 text-yellow-700', 'approved' => 'bg-green-100 text-green-700', 'rejected' => 'bg-red-100 text-red-700'];
                            $labels = ['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'];
                        @endphp
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $colors[$sv] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ $labels[$sv] ?? ucfirst($sv) }}
                        </span>
                    </dd>
                </div>
                @if($leave->reason)
                <div class="col-span-2">
                    <dt class="text-gray-500">Alasan</dt>
                    <dd class="mt-1 text-gray-700">{{ $leave->reason }}</dd>
                </div>
                @endif
            </dl>
            @if($leave->attachment)
                <div class="mt-4 border-t border-gray-100 pt-4">
                    <a href="{{ Storage::url($leave->attachment) }}" target="_blank"
                       class="inline-flex items-center gap-2 text-sm text-blue-600 hover:underline">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" />
                        </svg>
                        Lihat Lampiran
                    </a>
                </div>
            @endif
        </div>

        {{-- Riwayat Approval --}}
        @if($leave->approvals?->isNotEmpty())
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500">Riwayat Approval</h3>
            <ol class="relative border-l border-gray-200 pl-6 space-y-4">
                @foreach($leave->approvals->sortBy('level') as $approval)
                <li class="ml-2">
                    @php
                        $dv = is_object($approval->decision) ? $approval->decision->value : $approval->decision;
                        $dotColor = match($dv) { 'approved' => 'bg-green-500', 'rejected' => 'bg-red-500', default => 'bg-yellow-400' };
                    @endphp
                    <div class="absolute -left-1.5 mt-1.5 h-3 w-3 rounded-full border-2 border-white {{ $dotColor }}"></div>
                    <p class="text-sm font-medium text-gray-900">
                        Level {{ $approval->level }} — {{ $approval->approver?->full_name ?? '?' }}
                        <span class="ml-1 text-xs font-normal text-gray-500">({{ ucfirst($dv) }})</span>
                    </p>
                    @if($approval->decided_at)
                        <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($approval->decided_at)->diffForHumans() }}</p>
                    @endif
                    @if($approval->comment)
                        <p class="mt-1 rounded bg-gray-50 p-2 text-xs text-gray-700">{{ $approval->comment }}</p>
                    @endif
                </li>
                @endforeach
            </ol>
        </div>
        @endif

        {{-- Action Buttons --}}
        @if($canAct)
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500">Tindakan</h3>
            <div class="flex gap-3">
                <button wire:click="openReject"
                        class="rounded-lg border border-red-300 bg-red-50 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-100">
                    Tolak
                </button>
                <button wire:click="openApprove"
                        class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">
                    Setujui
                </button>
            </div>
        </div>
        @endif

    </div>

    {{-- Modal Approve/Reject --}}
    <x-ui.modal name="leave-action" max-width="md">
        <x-slot:title>
            {{ $pendingAction === 'approve' ? 'Setujui Pengajuan Cuti' : 'Tolak Pengajuan Cuti' }}
        </x-slot:title>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Komentar {{ $pendingAction === 'reject' ? '(wajib)' : '(opsional)' }}
            </label>
            <textarea wire:model="comment" rows="3"
                      placeholder="{{ $pendingAction === 'reject' ? 'Jelaskan alasan penolakan...' : 'Tambahkan komentar (opsional)' }}"
                      class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
            @error('comment') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <x-slot:footer>
            <button @click="$dispatch('close-modal', { name: 'leave-action' })"
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
