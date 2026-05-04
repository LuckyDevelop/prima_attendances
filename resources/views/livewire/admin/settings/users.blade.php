<div>
    <x-admin.page-header title="User &amp; Role" />

    {{-- Filters --}}
    <div class="mb-4 flex flex-wrap items-center gap-3">
        <div class="relative flex-1 min-w-[220px] max-w-sm">
            <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                 fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
            </svg>
            <input type="text" wire:model.live.debounce.400ms="search"
                   placeholder="Cari nama, email, atau NIK..."
                   class="w-full rounded-lg border-gray-300 pl-9 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
        </div>

        <select wire:model.live="role"
                class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">Semua Role</option>
            @foreach($roles as $r)
                <option value="{{ $r->value }}">{{ $r->label() }}</option>
            @endforeach
        </select>

        <select wire:model.live="status"
                class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">Semua Status</option>
            @foreach($statuses as $s)
                <option value="{{ $s->value }}">{{ $s->label() }}</option>
            @endforeach
        </select>
    </div>

    {{-- Table --}}
    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Karyawan</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Email</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Departemen</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Role</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($users as $u)
                @php
                    $sv = is_object($u->status) ? $u->status->value : $u->status;
                    $rv = is_object($u->role) ? $u->role->value : $u->role;
                    $isSelf = $u->id === auth()->id();
                @endphp
                <tr wire:key="user-{{ $u->id }}" class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="h-9 w-9 flex-shrink-0 overflow-hidden rounded-full bg-blue-100">
                                @if($u->photo)
                                    <img src="{{ Storage::url($u->photo) }}" class="h-full w-full object-cover" alt="" />
                                @else
                                    <span class="flex h-full w-full items-center justify-center text-xs font-semibold text-blue-600">
                                        {{ strtoupper(substr($u->full_name, 0, 2)) }}
                                    </span>
                                @endif
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">
                                    {{ $u->full_name }}
                                    @if($isSelf)
                                        <span class="ml-1 text-xs text-blue-500">(Anda)</span>
                                    @endif
                                </p>
                                <p class="text-xs text-gray-500">{{ $u->employee_id }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $u->email }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $u->department?->name ?? '—' }}</td>

                    {{-- Role dropdown --}}
                    <td class="px-4 py-3">
                        <select wire:change="changeRole({{ $u->id }}, $event.target.value)"
                                {{ $isSelf ? 'disabled' : '' }}
                                class="rounded-md border-gray-300 text-xs shadow-sm focus:border-blue-500 focus:ring-blue-500 disabled:cursor-not-allowed disabled:bg-gray-50">
                            @foreach($roles as $r)
                                <option value="{{ $r->value }}" @selected($rv === $r->value)>
                                    {{ $r->label() }}
                                </option>
                            @endforeach
                        </select>
                    </td>

                    {{-- Status badge --}}
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                            {{ $sv === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                            {{ $sv === 'active' ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </td>

                    <td class="px-4 py-3 text-right">
                        @if(!$isSelf)
                            <button wire:click="toggleStatus({{ $u->id }})"
                                    wire:confirm="{{ $sv === 'active' ? 'Nonaktifkan' : 'Aktifkan' }} {{ $u->full_name }}?"
                                    class="text-xs font-medium {{ $sv === 'active' ? 'text-red-600 hover:text-red-800' : 'text-green-600 hover:text-green-800' }}">
                                {{ $sv === 'active' ? 'Nonaktifkan' : 'Aktifkan' }}
                            </button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-12">
                        <x-admin.empty-state title="Tidak ada user" description="Tidak ada user yang cocok dengan filter ini." />
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($users->hasPages())
        <div class="mt-4">{{ $users->links() }}</div>
    @endif
</div>
