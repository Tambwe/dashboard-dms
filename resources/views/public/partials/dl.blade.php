<dl class="divide-y divide-gray-100 dark:divide-gray-700 text-sm">
    @foreach($rows as [$label, $value])
    <div class="flex justify-between gap-3 py-1.5">
        <dt class="text-gray-500 dark:text-gray-400 font-medium shrink-0">{{ $label }}</dt>
        <dd class="text-gray-800 dark:text-gray-200 text-right break-words">{{ $value !== null && $value !== '' ? $value : '—' }}</dd>
    </div>
    @endforeach
</dl>
