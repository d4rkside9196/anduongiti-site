@props(['locale'])
@php
  use App\Models\Setting;
  $s = Setting::read('stats', ['years_experience' => 5, 'export_markets' => 12, 'farming_households' => 1200, 'partner_facilities' => 6]);
  $items = $locale === 'vi'
    ? [['v' => $s['years_experience'], 'sfx' => '+', 'l' => 'Năm kinh nghiệm', 's' => 'Từ 2020 đến nay'],
       ['v' => $s['export_markets'], 'sfx' => '+', 'l' => 'Quốc gia xuất khẩu', 's' => 'Tập trung EU & UK'],
       ['v' => $s['farming_households'], 'sfx' => '+', 'l' => 'Hộ nông dân đối tác', 's' => 'Trên cả nước'],
       ['v' => $s['partner_facilities'], 'sfx' => '', 'l' => 'Nhà máy đối tác', 's' => 'OEM/ODM hợp chuẩn']]
    : [['v' => $s['years_experience'], 'sfx' => '+', 'l' => 'Years of experience', 's' => 'Since 2020'],
       ['v' => $s['export_markets'], 'sfx' => '+', 'l' => 'Export markets', 's' => 'EU & UK focus'],
       ['v' => $s['farming_households'], 'sfx' => '+', 'l' => 'Partner farming households', 's' => 'Across Vietnam'],
       ['v' => $s['partner_facilities'], 'sfx' => '', 'l' => 'Partner facilities', 's' => 'OEM/ODM certified']];
@endphp
<section class="border-y border-earth-200/60 bg-white/40">
  <div class="container-x grid gap-10 py-16 sm:grid-cols-2 lg:grid-cols-4">
    @foreach ($items as $it)
      <div class="text-center sm:text-left">
        <div class="flex items-baseline justify-center gap-1 sm:justify-start">
          <span class="counter font-serif text-5xl md:text-6xl font-medium tracking-tight text-forest-800" data-target="{{ $it['v'] }}">0</span>
          <span class="font-serif text-3xl md:text-4xl font-medium text-gold">{{ $it['sfx'] }}</span>
        </div>
        <p class="mt-2 text-sm font-semibold uppercase tracking-widest text-earth-700">{{ $it['l'] }}</p>
        <p class="mt-1 text-sm text-ink/65">{{ $it['s'] }}</p>
      </div>
    @endforeach
  </div>
</section>
<script>
  document.querySelectorAll('.counter').forEach((el) => {
    const target = Number(el.dataset.target ?? 0);
    const animate = () => {
      const start = performance.now();
      const tick = (now) => {
        const t = Math.min(1, (now - start) / 1400);
        el.textContent = Math.round(target * (1 - Math.pow(1 - t, 3))).toLocaleString();
        if (t < 1) requestAnimationFrame(tick);
      };
      requestAnimationFrame(tick);
    };
    new IntersectionObserver((es, o) => {
      for (const e of es) if (e.isIntersecting) { animate(); o.unobserve(e.target); }
    }, { threshold: 0.4 }).observe(el);
  });
</script>
