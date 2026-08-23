@props([
    'title',
    'icon',
    'active' => false,
])

<div x-data="{ open: {{ $active ? 'true' : 'false' }} }">
    <button
        type="button"
        @click="open = !open"
        :aria-expanded="open"
        class="flex w-full items-center justify-between rounded-lg px-4 py-3 text-sm font-medium transition-colors {{ $active ? 'bg-primary-50 text-primary-600 dark:bg-primary-900/20 dark:text-primary-400' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}"
    >
        <span class="flex min-w-0 items-center">
            <span class="mr-3 flex h-6 w-6 shrink-0 items-center justify-center text-base" aria-hidden="true">{{ $icon }}</span>
            <span class="truncate">{{ $title }}</span>
        </span>
        <svg
            class="h-4 w-4 shrink-0 transition-transform duration-200"
            :class="open ? 'rotate-180' : ''"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            aria-hidden="true"
        >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    <div x-cloak x-show="open" x-transition class="mt-1 space-y-1 pl-5">
        {{ $slot }}
    </div>
</div>
