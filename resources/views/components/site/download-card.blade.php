@props(['href', 'title', 'subtitle', 'cover' => null, 'cta' => 'View online', 'downloadLabel' => 'Download', 'variant' => 'feature'])

@if ($variant === 'feature')
  <article class="group flex flex-col overflow-hidden rounded-3xl border border-earth-200/60 bg-white/70 transition hover:shadow-2xl hover:shadow-forest-900/10 sm:flex-row">
    <a href="{{ $href }}" target="_blank" rel="noopener" class="block sm:w-2/5">
      @if ($cover)
        <div class="aspect-[4/3] sm:aspect-[16/10] sm:h-full bg-earth-50 overflow-hidden">
          <img src="{{ $cover }}" alt="" class="h-full w-full object-cover transition group-hover:scale-105" loading="lazy" />
        </div>
      @endif
    </a>
    <div class="flex flex-1 flex-col justify-center p-8">
      <p class="eyebrow">PDF</p>
      <h3 class="mt-2 font-serif text-2xl font-medium text-ink leading-tight">{{ $title }}</h3>
      <p class="mt-2 text-sm text-ink/60">{{ $subtitle }}</p>
      <div class="mt-6 flex flex-wrap items-center gap-3">
        <a href="{{ $href }}" target="_blank" rel="noopener" class="btn-primary">
          <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
          {{ $cta }}
        </a>
        <a href="{{ $href }}" download class="btn-ghost">
          <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 4v12M5 13l7 7 7-7M4 22h16"/></svg>
          {{ $downloadLabel }}
        </a>
      </div>
    </div>
  </article>
@else
  <div class="group flex items-center gap-3 rounded-2xl border border-earth-200/60 bg-white/70 p-4 transition hover:bg-white hover:shadow-lg hover:shadow-forest-900/5">
    <a href="{{ $href }}" target="_blank" rel="noopener" class="flex flex-1 items-center gap-4 min-w-0">
      @if ($cover)
        <img src="{{ $cover }}" alt="" class="h-16 w-24 shrink-0 rounded-lg object-cover" loading="lazy" />
      @else
        <div class="grid h-16 w-16 shrink-0 place-items-center rounded-lg bg-forest-50 text-forest-700">
          <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
      @endif
      <div class="min-w-0 flex-1">
        <p class="text-xs font-semibold uppercase tracking-widest text-earth-700">PDF · {{ $cta }}</p>
        <p class="mt-0.5 truncate font-semibold text-ink">{{ $title }}</p>
        <p class="truncate text-xs text-ink/65">{{ $subtitle }}</p>
      </div>
    </a>
    <a href="{{ $href }}" download class="grid h-12 w-12 shrink-0 place-items-center rounded-lg border border-earth-200 text-forest-700 transition hover:bg-forest-50" aria-label="{{ $downloadLabel }}">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 4v12M5 13l7 7 7-7M4 22h16"/></svg>
    </a>
  </div>
@endif
