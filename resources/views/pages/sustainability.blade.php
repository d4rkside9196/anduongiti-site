@php
  $pillars = $locale === 'vi' ? [
    ['t' => 'Không phá rừng (EUDR)', 'b' => 'Mọi lô cà phê và cao su đi EU đều có toạ độ vùng trồng và bằng chứng không phá rừng từ sau 31/12/2020.'],
    ['t' => 'Quản lý rừng FSC', 'b' => 'Cao su FSC CoC khi đối tác yêu cầu, đảm bảo chuỗi hành trình minh bạch từ vườn đến cảng.'],
    ['t' => 'An toàn thực phẩm', 'b' => 'Nhà máy đối tác đạt ISO 22000, HACCP, BRC, IFS và đăng ký FDA Hoa Kỳ.'],
    ['t' => 'Tác động cộng đồng', 'b' => 'Đầu tư đào tạo kỹ thuật canh tác và thu mua công bằng cho hơn 1.200 hộ nông dân.'],
  ] : [
    ['t' => 'Deforestation-free (EUDR)', 'b' => 'Every EU-bound coffee and rubber lot ships with GPS coordinates and proof of no deforestation since 31 Dec 2020.'],
    ['t' => 'FSC forest stewardship', 'b' => 'Rubber with FSC Chain-of-Custody on request, ensuring transparent traceability from grove to port.'],
    ['t' => 'Food safety', 'b' => 'Partner facilities certified ISO 22000, HACCP, BRC, IFS and U.S. FDA registered.'],
    ['t' => 'Community impact', 'b' => 'Through cooperative networks, we invest in farming-technique training for 1,200+ households.'],
  ];
@endphp
<x-layouts.site :locale="$locale" :title="__('site.sustainability.title')">
  <x-site.page-header :eyebrow="__('site.sustainability.eyebrow')" :title="__('site.sustainability.title')" :lede="__('site.sustainability.lede')" />

  <section class="container-x py-20">
    <div class="grid gap-px bg-earth-200/60 rounded-3xl overflow-hidden md:grid-cols-2">
      @foreach ($pillars as $p)
        <article class="bg-cream p-10">
          <h3 class="text-2xl font-medium text-ink">{{ $p['t'] }}</h3>
          <p class="mt-3 text-ink/70 leading-relaxed">{{ $p['b'] }}</p>
        </article>
      @endforeach
    </div>
  </section>

  <x-site.certifications :locale="$locale" />
</x-layouts.site>
