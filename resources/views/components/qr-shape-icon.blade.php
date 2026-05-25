@props(['shape', 'size' => 32])

<svg viewBox="0 0 24 24" width="{{ $size }}" height="{{ $size }}" class="shrink-0 text-gray-900" {{ $attributes }}>
@switch($shape)
    {{-- Body shapes --}}
    @case('body-square')
        <rect x="2" y="2" width="20" height="20" fill="currentColor"/>
        @break
    @case('body-rounded-connected')
        {{-- 2x2 grid: TL+TR connected top, BL standalone, BR standalone --}}
        <path d="M2,7 Q2,2 7,2 L17,2 Q22,2 22,7 L22,11 L2,11 Z" fill="currentColor"/>
        <rect x="2" y="13" width="9" height="9" rx="4" fill="currentColor"/>
        <rect x="13" y="13" width="9" height="9" rx="4" fill="currentColor"/>
        @break
    @case('body-extra-rounded-connected')
        <path d="M2,7 Q2,2 7,2 L17,2 Q22,2 22,7 L22,11 L2,11 Z" fill="currentColor"/>
        <circle cx="6.5" cy="17.5" r="4.5" fill="currentColor"/>
        <circle cx="17.5" cy="17.5" r="4.5" fill="currentColor"/>
        @break
    @case('body-classy')
        {{-- Classy: rounded TL and BR only --}}
        <path d="M2,7 Q2,2 7,2 L22,2 L22,11 L2,11 Z" fill="currentColor"/>
        <path d="M2,13 L22,13 L22,17 Q22,22 17,22 L2,22 Z" fill="currentColor"/>
        @break
    @case('body-classy-rounded')
        <path d="M2,8 Q2,2 8,2 L22,2 L22,11 L2,11 Z" fill="currentColor"/>
        <path d="M2,13 L22,13 L22,16 Q22,22 16,22 L2,22 Z" fill="currentColor"/>
        @break
    @case('body-dots')
        <circle cx="12" cy="12" r="8" fill="currentColor"/>
        @break
    @case('body-dots-small')
        <circle cx="12" cy="12" r="5" fill="currentColor"/>
        @break
    @case('body-diamond')
        <polygon points="12,2 22,12 12,22 2,12" fill="currentColor"/>
        @break
    @case('body-star')
        <rect x="6" y="2" width="12" height="20" fill="currentColor"/>
        <rect x="2" y="6" width="20" height="12" fill="currentColor"/>
        @break
    @case('body-rounded-square')
        <rect x="3" y="3" width="18" height="18" rx="4" fill="currentColor"/>
        @break
    @case('body-extra-rounded')
        <rect x="2" y="2" width="20" height="20" rx="6" fill="currentColor"/>
        @break
    @case('body-leaf')
        <path d="M2,2 L22,2 Q22,2 22,2 L22,16 Q22,22 16,22 L2,22 Q2,22 2,22 L2,8 Q2,2 8,2 Z" fill="currentColor"/>
        @break
    @case('body-octagon')
        <polygon points="8,2 16,2 22,8 22,16 16,22 8,22 2,16 2,8" fill="currentColor"/>
        @break
    @case('body-vertical-bars')
        <rect x="5" y="2" width="4" height="20" fill="currentColor"/>
        <rect x="15" y="2" width="4" height="20" fill="currentColor"/>
        @break
    @case('body-horizontal-bars')
        <rect x="2" y="5" width="20" height="4" fill="currentColor"/>
        <rect x="2" y="15" width="20" height="4" fill="currentColor"/>
        @break
    @case('body-plus')
        <rect x="10" y="2" width="4" height="20" fill="currentColor"/>
        <rect x="2" y="10" width="20" height="4" fill="currentColor"/>
        @break
    @case('body-grid')
        <circle cx="8" cy="8" r="2.5" fill="currentColor"/>
        <circle cx="16" cy="8" r="2.5" fill="currentColor"/>
        <circle cx="8" cy="16" r="2.5" fill="currentColor"/>
        <circle cx="16" cy="16" r="2.5" fill="currentColor"/>
        @break

    {{-- Eye frame shapes --}}
    @case('frame-square')
        <rect x="3" y="3" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"/>
        @break
    @case('frame-rounded')
        <rect x="3" y="3" width="18" height="18" rx="4" fill="none" stroke="currentColor" stroke-width="2"/>
        @break
    @case('frame-circle')
        <circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2"/>
        @break
    @case('frame-leaf')
        <path d="M3,9 Q3,3 9,3 L21,3 L21,15 Q21,21 15,21 L3,21 Z" fill="none" stroke="currentColor" stroke-width="2"/>
        @break
    @case('frame-octagon')
        <polygon points="8,3 16,3 21,8 21,16 16,21 8,21 3,16 3,8" fill="none" stroke="currentColor" stroke-width="2"/>
        @break
    @case('frame-cushion')
        <path d="M3,8 Q3,3 8,3 L16,3 Q21,3 21,8 L21,16 Q21,21 16,21 L8,21 Q3,21 3,16 Z" fill="none" stroke="currentColor" stroke-width="2"/>
        @break
    @case('frame-dot')
        <circle cx="12" cy="12" r="6" fill="none" stroke="currentColor" stroke-width="2"/>
        @break
    @case('frame-rounded-double')
        <rect x="2" y="2" width="20" height="20" rx="6" fill="none" stroke="currentColor" stroke-width="2"/>
        <rect x="5" y="5" width="14" height="14" rx="3" fill="none" stroke="currentColor" stroke-width="1.5"/>
        @break
    @case('frame-dotted')
        <circle cx="6" cy="6" r="1.5" fill="currentColor"/>
        <circle cx="12" cy="6" r="1.5" fill="currentColor"/>
        <circle cx="18" cy="6" r="1.5" fill="currentColor"/>
        <circle cx="6" cy="12" r="1.5" fill="currentColor"/>
        <circle cx="18" cy="12" r="1.5" fill="currentColor"/>
        <circle cx="6" cy="18" r="1.5" fill="currentColor"/>
        <circle cx="12" cy="18" r="1.5" fill="currentColor"/>
        <circle cx="18" cy="18" r="1.5" fill="currentColor"/>
        @break
    @case('frame-rounded-single')
        <rect x="3" y="3" width="18" height="18" rx="2" fill="none" stroke="currentColor" stroke-width="2"/>
        @break

    {{-- Eye ball shapes --}}
    @case('ball-square')
        <rect x="4" y="4" width="16" height="16" fill="currentColor"/>
        @break
    @case('ball-rounded')
        <rect x="4" y="4" width="16" height="16" rx="3" fill="currentColor"/>
        @break
    @case('ball-circle')
        <circle cx="12" cy="12" r="8" fill="currentColor"/>
        @break
    @case('ball-leaf')
        <path d="M4,10 Q4,4 10,4 L20,4 L20,14 Q20,20 14,20 L4,20 Z" fill="currentColor"/>
        @break
    @case('ball-diamond')
        <polygon points="12,4 20,12 12,20 4,12" fill="currentColor"/>
        @break
    @case('ball-star')
        <rect x="8" y="4" width="8" height="16" fill="currentColor"/>
        <rect x="4" y="8" width="16" height="8" fill="currentColor"/>
        @break
    @case('ball-dot')
        <circle cx="12" cy="12" r="5" fill="currentColor"/>
        @break
    @case('ball-squircle')
        <rect x="3" y="3" width="18" height="18" rx="5" fill="currentColor"/>
        @break
    @case('ball-bars-vertical')
        <rect x="6" y="4" width="3" height="16" fill="currentColor"/>
        <rect x="10.5" y="4" width="3" height="16" fill="currentColor"/>
        <rect x="15" y="4" width="3" height="16" fill="currentColor"/>
        @break
    @case('ball-bars-horizontal')
        <rect x="4" y="6" width="16" height="3" fill="currentColor"/>
        <rect x="4" y="10.5" width="16" height="3" fill="currentColor"/>
        <rect x="4" y="15" width="16" height="3" fill="currentColor"/>
        @break

    @default
        <rect x="4" y="4" width="16" height="16" fill="currentColor"/>
@endswitch
</svg>
