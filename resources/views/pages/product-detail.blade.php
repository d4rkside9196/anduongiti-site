@php use Mcamara\LaravelLocalization\Facades\LaravelLocalization; @endphp
<x-layouts.site :locale="$locale" :title="$product->title">
  <x-site.page-header :eyebrow="$product->eyebrow" :title="$product->title" :lede="$product->lede" />

  <section class="container-x py-20 grid lg:grid-cols-3 gap-12">
    <div class="lg:col-span-2">
      <p class="eyebrow">{{ $locale === 'vi' ? 'Thông số kỹ thuật' : 'Specifications' }}</p>
      <dl class="mt-6 divide-y divide-earth-200/70 rounded-3xl border border-earth-200/60 bg-white/60 overflow-hidden">
        @foreach ($product->specs as [$k, $v])
          <div class="grid grid-cols-3 gap-4 px-6 py-5">
            <dt class="text-sm font-semibold text-ink">{{ $k }}</dt>
            <dd class="col-span-2 text-sm text-ink/70 leading-relaxed">{{ $v }}</dd>
          </div>
        @endforeach
      </dl>
    </div>
    <aside class="rounded-3xl bg-forest-50 p-8">
      <p class="eyebrow">{{ $locale === 'vi' ? 'Vùng nguyên liệu' : 'Origins' }}</p>
      <ul class="mt-6 space-y-3">
        @foreach ($product->origins as $o)
          <li class="flex items-start gap-3 text-sm text-ink"><span class="mt-1.5 h-2 w-2 rounded-full bg-forest-500"></span>{{ $o }}</li>
        @endforeach
      </ul>
      <a href="{{ LaravelLocalization::getLocalizedURL($locale, '/contact') }}" class="btn-primary mt-8 w-full justify-center">
        {{ __('site.cta.discuss') }}
      </a>
    </aside>
  </section>

  @if ($others->count())
    <section class="container-x pb-24">
      <p class="eyebrow">{{ $locale === 'vi' ? 'Sản phẩm khác' : 'Other products' }}</p>
      <div class="mt-6 grid gap-6 sm:grid-cols-2">
        @foreach ($others as $p)
          <a href="{{ LaravelLocalization::getLocalizedURL($locale, '/products/'.$p->product_key) }}"
             class="group flex items-center justify-between rounded-2xl border border-earth-200/60 bg-white/60 p-6 transition hover:bg-white hover:shadow-lg">
            <span class="text-lg font-medium text-ink">
              {{ $p->title }}
              <span class="block text-sm font-normal text-ink/50">{{ $p->eyebrow }}</span>
            </span>
            <svg class="h-4 w-4 text-forest-700 transition group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
          </a>
        @endforeach
      </div>
    </section>
  @endif
</x-layouts.site>
