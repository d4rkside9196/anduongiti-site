@php
  use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
  use League\CommonMark\CommonMarkConverter;
  $converter = new CommonMarkConverter(['html_input' => 'escape', 'allow_unsafe_links' => false]);
  $html = $converter->convert($post->body);
@endphp
<x-layouts.site :locale="$locale" :title="$post->title" :description="$post->excerpt">
  <article class="container-x max-w-3xl py-24">
    <a href="{{ LaravelLocalization::getLocalizedURL($locale, '/news') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-forest-700 hover:text-forest-800">
      <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M11 19l-7-7 7-7"/></svg>
      {{ $locale === 'vi' ? 'Tất cả bài viết' : 'All articles' }}
    </a>
    <header class="mt-8">
      <div class="flex flex-wrap gap-3 text-xs text-earth-700">
        <time>{{ $post->published_at->locale($locale === 'vi' ? 'vi' : 'en')->isoFormat('D MMMM YYYY') }}</time>
        @foreach ($post->tags ?? [] as $tag)
          <span class="rounded-full bg-forest-50 px-2.5 py-1 text-forest-700 font-semibold">{{ $tag }}</span>
        @endforeach
      </div>
      <h1 class="mt-5 text-4xl sm:text-5xl font-medium leading-[1.05] tracking-tight text-ink">{{ $post->title }}</h1>
      <p class="mt-5 text-lg text-ink/70 leading-relaxed">{{ $post->excerpt }}</p>
    </header>
    @if ($post->cover)
      <img src="{{ $post->cover }}" alt="{{ $post->title }}" class="mt-10 w-full rounded-3xl object-cover aspect-[16/9]" />
    @endif
    <div class="prose mt-12 max-w-none prose-headings:font-serif prose-headings:text-ink prose-headings:font-medium prose-p:text-ink/80 prose-p:leading-relaxed prose-a:text-forest-700 prose-a:no-underline hover:prose-a:underline prose-strong:text-ink prose-img:rounded-2xl prose-blockquote:border-l-forest-500 prose-blockquote:bg-forest-50/50 prose-blockquote:py-1 prose-blockquote:not-italic">
      {!! $html !!}
    </div>
    <footer class="mt-16 border-t border-earth-200 pt-8 text-sm text-ink/60">
      An Duong ITI
    </footer>
  </article>
</x-layouts.site>
