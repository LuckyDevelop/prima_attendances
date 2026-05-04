@props([
    'label'       => '',
    'name'        => '',
    'error'       => null,
    'hint'        => '',
    'required'    => false,
])

<div>
    @if($label)
    <label for="{{ $name }}"
           class="mb-1 block text-sm font-medium text-gray-700">
        {{ $label }}
        @if($required) <span class="text-red-500">*</span> @endif
    </label>
    @endif

    <input id="{{ $name }}"
           name="{{ $name }}"
           {{ $attributes->class([
               'block w-full rounded-lg border px-3 py-2 text-sm shadow-sm transition-colors focus:outline-none focus:ring-2',
               'border-gray-300 focus:border-blue-500 focus:ring-blue-500' => ! $error,
               'border-red-300 focus:border-red-500 focus:ring-red-500 bg-red-50' => $error,
           ]) }} />

    @if($hint && ! $error)
    <p class="mt-1 text-xs text-gray-500">{{ $hint }}</p>
    @endif

    @if($error)
    <p class="mt-1 text-xs text-red-600">{{ $error }}</p>
    @endif
</div>
