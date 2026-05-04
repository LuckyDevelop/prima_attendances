<div>
    <x-admin.page-header title="Daftar Karyawan">
        <x-slot:actions>
            <x-ui.button href="{{ route('admin.employees.create') }}" wire:navigate>
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Karyawan
            </x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    {{-- Filters --}}
    <x-ui.card class="mb-5">
        <div class="flex flex-wrap items-end gap-3">
            {{-- Search --}}
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-gray-700 mb-1">Cari</label>
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400"
                         fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.197 5.197a7.5 7.5 0 0 0 10.606 10.606z" />
                    </svg>
                    <input wire:model.live.debounce.400ms="search"
                           type="text"
                           placeholder="Nama, email, atau NIK..."
                           class="w-full rounded-lg border-gray-300 pl-9 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                </div>
            </div>

            {{-- Department --}}
            <div class="min-w-[160px]">
                <label class="block text-xs font-medium text-gray-700 mb-1">Departemen</label>
                <select wire:model.live="departmentId"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Semua</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Role --}}
            <div class="min-w-[140px]">
                <label class="block text-xs font-medium text-gray-700 mb-1">Role</label>
                <select wire:model.live="role"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Semua</option>
                    @foreach($roles as $r)
                        <option value="{{ $r->value }}">{{ $r->label() }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Status --}}
            <div class="min-w-[130px]">
                <label class="block text-xs font-medium text-gray-700 mb-1">Status</label>
                <select wire:model.live="status"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Semua</option>
                    @foreach($statuses as $s)
                        <option value="{{ $s->value }}">{{ ucfirst($s->value) }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Reset --}}
            @if($search || $departmentId || $role || $status)
            <button wire:click="resetFilters"
                    class="text-sm text-gray-500 hover:text-gray-700 underline whitespace-nowrap">
                Reset filter
            </button>
            @endif
        </div>
    </x-ui.card>

    {{-- Table --}}
    <x-admin.data-table>
        <x-slot:head>
            <tr>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">NIK</th>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Karyawan</th>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Departemen</th>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Role</th>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Aksi</th>
            </tr>
        </x-slot:head>

        @forelse($users as $user)
        <tr class="hover:bg-gray-50 transition-colors" wire:key="user-{{ $user->id }}">
            <td class="px-6 py-4 text-sm font-mono text-gray-600">{{ $user->employee_id }}</td>

            <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                    <x-ui.avatar
                        :name="$user->full_name"
                        :src="$user->photo ? Storage::url($user->photo) : null"
                        class="h-9 w-9 flex-shrink-0" />
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ $user->full_name }}</p>
                        <p class="text-xs text-gray-500">{{ $user->email }}</p>
                    </div>
                </div>
            </td>

            <td class="px-6 py-4 text-sm text-gray-600">
                {{ $user->department?->name ?? '—' }}
            </td>

            <td class="px-6 py-4">
                <x-ui.badge :status="$user->role->value">{{ $user->role->label() }}</x-ui.badge>
            </td>

            <td class="px-6 py-4">
                <x-ui.badge :status="$user->status->value">{{ ucfirst($user->status->value) }}</x-ui.badge>
            </td>

            <td class="px-6 py-4 text-right">
                <div class="flex items-center justify-end gap-2">
                    <a href="{{ route('admin.employees.show', $user->id) }}"
                       wire:navigate
                       class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-700"
                       title="Detail">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.641 0-8.573-3.007-9.964-7.178z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z" />
                        </svg>
                    </a>

                    <a href="{{ route('admin.employees.edit', $user->id) }}"
                       wire:navigate
                       class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 hover:text-blue-600"
                       title="Edit">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931z" />
                        </svg>
                    </a>

                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" @click.outside="open = false"
                                class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-700">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5zM12 12.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5zM12 18.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5z" />
                            </svg>
                        </button>

                        <div x-show="open"
                             x-transition
                             class="absolute right-0 z-10 mt-1 w-48 rounded-xl border border-gray-200 bg-white py-1 shadow-lg">
                            <button @click="open = false"
                                    wire:click="toggleStatus({{ $user->id }})"
                                    class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50">
                                {{ $user->status->value === 'active' ? 'Nonaktifkan' : 'Aktifkan' }}
                            </button>
                            <button @click="open = false"
                                    wire:click="resetPassword({{ $user->id }})"
                                    wire:confirm="Reset password karyawan ini?"
                                    class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50">
                                Reset Password
                            </button>
                            <hr class="my-1 border-gray-100">
                            <button @click="open = false; $wire.confirmDelete({{ $user->id }})"
                                    class="w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50">
                                Hapus
                            </button>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" class="px-6 py-12">
                <x-admin.empty-state
                    title="Tidak ada karyawan"
                    description="Belum ada karyawan yang sesuai dengan filter." />
            </td>
        </tr>
        @endforelse

        <x-slot:footer>
            <div class="flex items-center justify-between text-sm text-gray-500">
                <span>Total {{ $users->total() }} karyawan</span>
                <div>{{ $users->links() }}</div>
            </div>
        </x-slot:footer>
    </x-admin.data-table>

    {{-- Delete confirmation modal --}}
    <x-admin.confirm-modal
        name="delete-employee"
        title="Hapus Karyawan?"
        message="Data karyawan akan dihapus secara permanen. Tindakan ini tidak dapat dibatalkan."
        confirm-text="Hapus"
        danger
    />

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('confirmed-delete-employee', () => {
                @this.deleteEmployee();
            });
        });
    </script>
</div>
