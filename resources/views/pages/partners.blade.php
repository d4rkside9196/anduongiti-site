@php use Mcamara\LaravelLocalization\Facades\LaravelLocalization; @endphp
<x-layouts.site :locale="$locale" :title="$locale === 'vi' ? 'Đối tác' : 'Partners'">
  <x-site.page-header
    :eyebrow="$locale === 'vi' ? 'Mạng lưới đối tác' : 'Partner network'"
    :title="$locale === 'vi' ? 'Một mạng lưới, ba miền, đầy đủ giấy tờ' : 'One network, three regions, full documentation'"
    :lede="$locale === 'vi'
        ? 'Chúng tôi không sở hữu nhà máy — chúng tôi vận hành một mạng lưới đối tác đã được chứng nhận, cho phép linh hoạt theo nhu cầu của từng đơn hàng và thị trường đích.'
        : 'We do not own factories — we operate a network of certified partners that flexes to each order and destination market.'"
  />

  <section class="container-x py-20">
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
      @foreach ($partners as $p)
        <article class="rounded-2xl border border-earth-200/60 bg-white/70 p-7 transition hover:bg-white hover:shadow-lg hover:shadow-forest-900/5">
          <div class="flex h-14 items-center">
            <div class="font-serif text-2xl font-medium tracking-tight text-forest-800">{{ $p->name }}</div>
          </div>
          <p class="mt-3 text-xs uppercase tracking-widest text-earth-700">{{ $p->region }}</p>
          <p class="mt-3 text-sm text-ink/70 leading-relaxed">{{ $locale === 'vi' ? $p->spec_vi : $p->spec_en }}</p>
        </article>
      @endforeach
    </div>
  </section>

  <x-site.process :locale="$locale" />

  <section class="container-x pb-24">
    <div class="rounded-3xl bg-forest-50 p-10 sm:p-14 text-center">
      <p class="eyebrow">{{ $locale === 'vi' ? 'Sẵn sàng' : 'Ready' }}</p>
      <h2 class="mt-3 font-serif text-3xl sm:text-4xl text-ink">
        {{ $locale === 'vi' ? 'Trở thành đối tác hoặc đặt hàng đầu tiên' : 'Become a partner — or place a first order' }}
      </h2>
      <a href="{{ LaravelLocalization::getLocalizedURL($locale, '/contact') }}" class="btn-primary mt-7">
        {{ __('site.cta.get_in_touch') }}
      </a>
    </div>
  </section>
</x-layouts.site>
