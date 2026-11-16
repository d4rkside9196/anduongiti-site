@props(['eyebrow' => null, 'title', 'lede' => null])
<section class="relative overflow-hidden border-b border-earth-200/60 bg-cream pt-20 pb-20 sm:pt-28 sm:pb-24">
  <div class="pointer-events-none absolute inset-0 -z-10">
    <div class="absolute -top-40 right-0 h-[420px] w-[420px] rounded-full bg-forest-100/50 blur-3xl"></div>
  </div>
  <div class="container-x">
    @if ($eyebrow)<p class="eyebrow">{{ $eyebrow }}</p>@endif
    <h1 class="mt-3 text-4xl sm:text-5xl md:text-6xl font-medium leading-[1.05] tracking-tight text-ink">{{ $title }}</h1>
    @if ($lede)<p class="mt-6 max-w-2xl text-lg text-ink/70 leading-relaxed">{{ $lede }}</p>@endif
  </div>
</section>
