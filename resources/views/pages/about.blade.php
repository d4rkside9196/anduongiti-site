@php
  use App\Models\Setting;
  $values = $locale === 'vi' ? [
    ['n' => 'Uy tín', 'b' => 'Sự minh bạch và chính trực trong mọi giao dịch trên toàn bộ mạng lưới cung ứng.'],
    ['n' => 'Bền vững', 'b' => 'Cam kết tuân thủ các chuẩn mực quốc tế (EUDR, FSC) xuyên suốt từ vùng nguyên liệu đến nhà máy.'],
    ['n' => 'Chất lượng', 'b' => 'Kiểm soát nghiêm ngặt chất lượng tại từng nhà máy đối tác.'],
    ['n' => 'Mạng lưới', 'b' => 'Mạng lưới cung ứng nguồn nguyên liệu rộng khắp Việt Nam.'],
  ] : [
    ['n' => 'Integrity', 'b' => 'Transparency and honesty in every transaction across the entire supply network.'],
    ['n' => 'Sustainability', 'b' => 'Commitment to international standards (EUDR, FSC) from raw material regions to factories.'],
    ['n' => 'Quality', 'b' => 'Rigorous quality control at every partner facility.'],
    ['n' => 'Network', 'b' => 'A nationwide supply network — dedicated raw material regions.'],
  ];
  $pdf = config('site.profile_pdf');
@endphp
<x-layouts.site :locale="$locale" :title="__('site.about.title')">
  <x-site.page-header :eyebrow="__('site.about.eyebrow')" :title="__('site.about.title')" :lede="__('site.about.story')" />

  <section class="container-x py-24 grid lg:grid-cols-2 gap-12">
    <div class="rounded-3xl border border-earth-200/60 bg-white/60 p-10">
      <p class="eyebrow">{{ __('site.about.vision_title') }}</p>
      <p class="mt-4 text-xl leading-relaxed font-serif text-ink">{{ __('site.about.vision') }}</p>
    </div>
    <div class="rounded-3xl border border-earth-200/60 bg-forest-900 text-cream p-10">
      <p class="eyebrow !text-gold">{{ __('site.about.mission_title') }}</p>
      <p class="mt-4 text-xl leading-relaxed font-serif">{{ __('site.about.mission') }}</p>
    </div>
  </section>

  <section class="container-x pb-24">
    <p class="eyebrow">{{ __('site.about.values_title') }}</p>
    <div class="mt-8 grid gap-px bg-earth-200/60 rounded-3xl overflow-hidden lg:grid-cols-2">
      @foreach ($values as $v)
        <article class="bg-cream p-10">
          <h3 class="text-2xl font-medium text-ink">{{ $v['n'] }}</h3>
          <p class="mt-3 text-ink/70 leading-relaxed">{{ $v['b'] }}</p>
        </article>
      @endforeach
    </div>
  </section>

  <section class="container-x pb-24">
    <p class="eyebrow">{{ $locale === 'vi' ? 'Tài liệu giới thiệu' : 'Company brochure' }}</p>
    <h2 class="mt-3 max-w-2xl font-serif text-3xl sm:text-4xl font-medium leading-tight text-ink">
      {{ $locale === 'vi' ? 'Tải Company Profile chính thức của An Dương ITI' : 'Download An Duong ITI official Company Profile' }}
    </h2>
    <p class="mt-4 max-w-2xl text-ink/65 leading-relaxed">
      {{ $locale === 'vi' ? 'Hồ sơ năng lực 14 trang gồm tầm nhìn, sản phẩm, năng lực sản xuất, chứng chỉ và thông tin liên hệ.' : '14-page capability deck covering vision, products, manufacturing capability, certifications and contacts.' }}
    </p>
    <div class="mt-8 max-w-3xl">
      <x-site.download-card
        :href="$pdf['href']"
        :cover="$pdf['cover']"
        :title="$locale === 'vi' ? 'An Dương ITI · Company Profile' : 'An Duong ITI · Company Profile'"
        subtitle="{{ $pdf['pages'] }} {{ $locale === 'vi' ? 'trang' : 'pages' }} · PDF · {{ $pdf['size'] }}"
        :cta="__('site.cta.view_pdf')"
        :downloadLabel="__('site.cta.download')"
      />
    </div>
  </section>
</x-layouts.site>
