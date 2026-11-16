@props(['locale'])
@php
  $items = $locale === 'vi' ? [
      ['t' => 'Chất lượng vượt trội', 'b' => 'Hợp tác với các nhà máy chế biến hàng đầu đạt chuẩn HACCP, ISO 22000 và BRC — tuân thủ đầy đủ các quy định quốc tế và tiêu chuẩn an toàn thực phẩm khắt khe nhất.'],
      ['t' => 'Giải pháp tùy biến', 'b' => 'Công thức và quy cách đóng gói theo yêu cầu cụ thể của từng thị trường và từng đối tác (3-in-1, 2-in-1, đen, freeze-dried, packaging riêng).'],
      ['t' => 'Truy xuất nguồn gốc', 'b' => 'Minh bạch toàn chuỗi từ nguyên liệu đến thành phẩm — sẵn sàng hồ sơ DDS cho từng lô hàng đi EU.'],
      ['t' => 'Chuyên môn xuất khẩu', 'b' => 'Kinh nghiệm sâu trong các Hiệp định Thương mại Tự do thế hệ mới (UKVFTA, EVFTA) — xử lý mượt các quy định nhập khẩu và rào cản kỹ thuật phức tạp.'],
  ] : [
      ['t' => 'Quality Excellence', 'b' => 'We partner with elite manufacturing facilities certified to HACCP, ISO 22000 and BRC — full compliance with international regulatory frameworks and the most stringent global food safety standards.'],
      ['t' => 'Tailor-made Solutions', 'b' => "Customised formulas and packaging based on each market and each partner's specific requirements (3-in-1, 2-in-1, Black, freeze-dried, private label)."],
      ['t' => 'Traceability', 'b' => 'Full transparency in sourcing, from raw material to the final product — ready-to-submit DDS files for every EU-bound lot.'],
      ['t' => 'Export Mastery', 'b' => 'Extensive expertise in navigating New-Generation Free Trade Agreements — including UKVFTA and EVFTA — for seamless compliance with complex international import regulations and technical barriers to trade.'],
  ];
  $icons = [
    '<path d="M9 12l2 2 4-4M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
    '<path d="M12 2L4 7v6c0 5 3.5 8.5 8 9 4.5-.5 8-4 8-9V7l-8-5z"/>',
    '<path d="M3 7l9-4 9 4M3 7v10l9 4 9-4V7M3 7l9 4 9-4M12 11v10"/>',
    '<circle cx="12" cy="12" r="9"/><path d="M12 3v18M3 12h18M5 7l14 10M5 17l14-10"/>',
  ];
@endphp
<section class="container-x py-24">
  <x-site.section-title :eyebrow="$locale === 'vi' ? 'Vì sao chọn chúng tôi' : 'Why choose us'" :title="__('site.home.values_title')" />
  <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
    @foreach ($items as $i => $it)
      <article class="group relative rounded-2xl border border-earth-200/70 bg-white/60 p-7 transition hover:bg-white hover:shadow-xl hover:shadow-forest-900/5">
        <div class="grid h-12 w-12 place-items-center rounded-xl bg-forest-50 text-forest-700 transition group-hover:bg-forest-700 group-hover:text-cream">
          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">{!! $icons[$i] !!}</svg>
        </div>
        <h3 class="mt-5 text-lg font-semibold text-ink">{{ $it['t'] }}</h3>
        <p class="mt-2 text-sm leading-relaxed text-ink/65">{{ $it['b'] }}</p>
      </article>
    @endforeach
  </div>
</section>
