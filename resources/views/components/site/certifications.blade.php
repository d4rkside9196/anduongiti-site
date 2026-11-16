@props(['locale', 'compact' => false])
@php
  $certs = [
    ['EUDR', $locale === 'vi' ? 'Quy định không phá rừng EU' : 'EU Deforestation Regulation'],
    ['FSC', $locale === 'vi' ? 'Quản lý rừng bền vững' : 'Forest Stewardship Council'],
    ['BRC', $locale === 'vi' ? 'Tiêu chuẩn an toàn thực phẩm Anh' : 'British Retail Consortium'],
    ['IFS', $locale === 'vi' ? 'Tiêu chuẩn thực phẩm quốc tế' : 'International Featured Standards'],
    ['ISO 22000', $locale === 'vi' ? 'An toàn thực phẩm' : 'Food safety management'],
    ['HACCP', $locale === 'vi' ? 'Phân tích mối nguy' : 'Hazard analysis & critical control'],
    ['FDA', $locale === 'vi' ? 'Đăng ký FDA Hoa Kỳ' : 'U.S. FDA registration'],
    ['HALAL', $locale === 'vi' ? 'Chứng nhận Halal' : 'Halal certification'],
    ['KOSHER', $locale === 'vi' ? 'Chứng nhận Kosher' : 'Kosher (Kof-K) certification'],
    ['4C / UTZ', $locale === 'vi' ? 'Cà phê bền vững' : 'Sustainable coffee'],
  ];
@endphp
<section class="container-x {{ $compact ? 'py-16' : 'py-24' }}">
  @unless ($compact)
    <x-site.section-title :eyebrow="__('site.sustainability.eyebrow')" :title="__('site.home.certs_title')" align="center" />
  @endunless
  <div class="mt-10 grid gap-3 grid-cols-2 sm:grid-cols-5 {{ $compact ? 'lg:grid-cols-10' : '' }}">
    @foreach ($certs as [$name, $desc])
      <div class="group relative rounded-2xl border border-earth-200/60 bg-white/70 p-5 text-center transition hover:border-forest-300 hover:bg-white">
        <div class="font-serif text-xl font-semibold tracking-tight text-forest-800">{{ $name }}</div>
        <p class="mt-1 text-xs text-ink/65">{{ $desc }}</p>
      </div>
    @endforeach
  </div>
</section>
