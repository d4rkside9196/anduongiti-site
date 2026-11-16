@php use Mcamara\LaravelLocalization\Facades\LaravelLocalization; @endphp
<x-layouts.site :locale="$locale" :title="__('site.news.title')">
  <x-site.page-header :eyebrow="$locale === 'vi' ? 'Tin tức' : 'Insights'" :title="__('site.news.title')" :lede="__('site.news.lede')" />

  <section class="container-x py-20">
    @if ($posts->isEmpty())
      <p class="text-ink/60">{{ $locale === 'vi' ? 'Chưa có bài viết.' : 'No posts yet.' }}</p>
    @else
      <div class="grid gap-10 md:grid-cols-2 lg:grid-cols-3">
        @foreach ($posts as $post)
          <a href="{{ LaravelLocalization::getLocalizedURL($locale, '/news/'.$post->slug) }}"
             class="group flex flex-col rounded-3xl border border-earth-200/60 bg-white/70 overflow-hidden transition hover:-translate-y-1 hover:shadow-xl hover:shadow-forest-900/5">
            <div class="aspect-[16/10] bg-gradient-to-br from-forest-100 to-earth-100 grid place-items-center overflow-hidden">
              @if ($post->cover)
                <img src="{{ $post->cover }}" alt="{{ $post->title }}" class="h-full w-full object-cover transition group-hover:scale-105" loading="lazy" />
              @else
                <svg class="h-16 w-16 text-forest-700/40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M3 8h13a4 4 0 010 8h-1M3 8v8a4 4 0 004 4h5a4 4 0 004-4M6 4v2M10 4v2M14 4v2"/></svg>
              @endif
            </div>
            <div class="flex flex-1 flex-col p-7">
              <div class="flex flex-wrap gap-2 text-xs text-earth-700">
                <time>{{ $post->published_at->locale($locale === 'vi' ? 'vi' : 'en')->isoFormat('D MMM YYYY') }}</time>
                @if ($post->tags && count($post->tags))
                  <span class="text-forest-700">· {{ $post->tags[0] }}</span>
                @endif
              </div>
              <h3 class="mt-3 text-xl font-medium text-ink leading-snug group-hover:text-forest-700 transition">{{ $post->title }}</h3>
              <p class="mt-3 text-sm text-ink/65 leading-relaxed line-clamp-3 flex-1">{{ $post->excerpt }}</p>
              <span class="mt-5 inline-flex items-center gap-1.5 text-sm font-semibold text-forest-700">
                {{ $locale === 'vi' ? 'Đọc tiếp' : 'Read article' }}
                <svg class="h-3 w-3 transition group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
              </span>
            </div>
          </a>
        @endforeach
      </div>
    @endif
  </section>
</x-layouts.site>
