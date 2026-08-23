@props([
    'href',
    'active' => false,
])

<a
    href="{{ $href }}"
    {{ $attributes->class([
        'flex items-center rounded-lg px-4 py-2 text-sm transition-colors',
        'bg-primary-50 font-medium text-primary-600 dark:bg-primary-900/20 dark:text-primary-400' => $active,
        'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700' => ! $active,
    ]) }}
>
    <span class="mr-2 h-1.5 w-1.5 shrink-0 rounded-full {{ $active ? 'bg-primary-500' : 'bg-gray-300 dark:bg-gray-600' }}"></span>
    <span class="truncate">{{ $slot }}</span>
</a>
