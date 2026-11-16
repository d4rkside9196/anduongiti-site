@props(['eyebrow' => null, 'title', 'lede' => null, 'align' => 'left', 'tone' => 'light'])
<div class="max-w-3xl {{ $align === 'center' ? 'mx-auto text-center' : '' }}">
  @if ($eyebrow)
    <p class="eyebrow {{ $tone === 'dark' ? '!text-gold' : '' }}">{{ $eyebrow }}</p>
  @endif
  <h2 class="mt-3 text-3xl sm:text-4xl md:text-5xl font-medium leading-[1.05] {{ $tone === 'dark' ? 'text-cream' : 'text-ink' }}">
    {{ $title }}
  </h2>
  @if ($lede)
    <p class="mt-5 text-base sm:text-lg leading-relaxed {{ $tone === 'dark' ? 'text-cream/70' : 'text-ink/70' }}">{{ $lede }}</p>
  @endif
</div>
