@props([
    'label'    => '',
    'name'     => '',
    'error'    => null,
    'rows'     => 3,
    'required' => false,
])

<div>
    @if($label)
    <label for="{{ $name }}"
           class="mb-1 block text-sm font-medium text-gray-700">
        {{ $label }}
        @if($required) <span class="text-red-500">*</span> @endif
    </label>
    @endif

    <textarea id="{{ $name }}"
              name="{{ $name }}"
              rows="{{ $rows }}"
              {{ $attributes->class([
                  'block w-full rounded-lg border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 resize-none',
                  'border-gray-300 focus:border-blue-500 focus:ring-blue-500' => ! $error,
                  'border-red-300 focus:border-red-500 focus:ring-red-500 bg-red-50' => $error,
              ]) }}>{{ $slot }}</textarea>

    @if($error)
    <p class="mt-1 text-xs text-red-600">{{ $error }}</p>
    @endif
</div>
