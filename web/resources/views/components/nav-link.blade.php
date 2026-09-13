@props(['href', 'active' => false])

<a href="{{ $href }}" wire:navigate
   class="flex items-center gap-3 rounded-lg px-3 py-2.5 font-medium transition-colors {{ $active ? 'bg-campo text-white' : 'text-salvia hover:bg-white/10' }}">
    @isset($icon)
        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            {{ $icon }}
        </svg>
    @endisset
    <span>{{ $slot }}</span>
</a>
