@props([
    'headers' => [],
])

<div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                @if(count($headers))
                <tr>
                    @foreach($headers as $header)
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                        {{ $header }}
                    </th>
                    @endforeach
                </tr>
                @else
                {{ $head ?? '' }}
                @endif
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                {{ $slot }}
            </tbody>
        </table>
    </div>

    @isset($footer)
    <div class="border-t border-gray-200 bg-gray-50 px-6 py-3">
        {{ $footer }}
    </div>
    @endisset
</div>
