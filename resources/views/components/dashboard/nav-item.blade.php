@props([
    'href' => null,
    'icon',
    'active' => false,
    'disabled' => false,
    'badge' => null,
])
@if ($disabled || ! $href)
    <span
        class="flex cursor-not-allowed items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-pln-slate-300"
        title="Modul akan tersedia pada sprint berikutnya"
    >
        <x-icon :name="$icon" class="h-5 w-5 shrink-0" />
        <span class="flex-1">{{ $slot }}</span>
        @if ($badge)
            <span class="rounded-full bg-pln-slate-100 px-2 py-0.5 text-xs font-semibold text-pln-slate-400">{{ $badge }}</span>
        @endif
    </span>
@else
	<a
		href="{{ $href }}"
		@class([
			'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
			'bg-pln-navy-700 text-white shadow-sm' => $active,
			'text-pln-slate-600 hover:bg-pln-slate-100 hover:text-pln-navy-900' => ! $active,
		])
	>
		<x-icon :name="$icon" class="h-5 w-5 shrink-0" />
		<span class="flex-1">
			{{ $slot }}
		</span>
		@if ($badge)
			<span
				@class([
					'rounded-full px-2 py-0.5 text-xs font-semibold',
					'bg-white/20 text-white' => $active,
					'bg-pln-slate-100 text-pln-slate-500' => ! $active,
				])
			>
				{{ $badge }}
			</span>
		@endif
	</a>
@endif
