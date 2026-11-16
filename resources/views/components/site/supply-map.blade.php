@props(['locale'])
@php
  $markers = [
    ['x' => 50,    'y' => 67.5,  'l' => 'Sơn La',   's' => 'Arabica'],
    ['x' => 96.2,  'y' => 74.2,  'l' => 'Hà Nội',   's' => $locale === 'vi' ? 'Trụ sở · Cảng Hải Phòng' : 'HQ · Hai Phong port'],
    ['x' => 105,   'y' => 145,   'l' => 'Hà Tĩnh',  's' => $locale === 'vi' ? 'Cao su' : 'Rubber'],
    ['x' => 125,   'y' => 180,   'l' => 'Quảng Trị', 's' => $locale === 'vi' ? 'Cao su' : 'Rubber'],
    ['x' => 155,   'y' => 198.2, 'l' => 'Đà Nẵng',  's' => $locale === 'vi' ? 'Cảng XK miền Trung' : 'Central export port'],
    ['x' => 150,   'y' => 250,   'l' => 'Gia Lai',  's' => $locale === 'vi' ? 'Robusta & nhà máy' : 'Robusta & processing'],
    ['x' => 151.2, 'y' => 282.5, 'l' => 'Đắk Lắk',  's' => 'Robusta · Buôn Ma Thuột'],
    ['x' => 161.3, 'y' => 301.2, 'l' => 'Lâm Đồng', 's' => 'Arabica · Cầu Đất'],
    ['x' => 117.5, 'y' => 330,   'l' => 'TP.HCM',   's' => $locale === 'vi' ? 'Cảng Cát Lái · Trụ sở' : 'Cat Lai port · HQ'],
  ];
  $hoangSa = [['x' => 240, 'y' => 178.7], ['x' => 250, 'y' => 187.5], ['x' => 257.5, 'y' => 178.7], ['x' => 245, 'y' => 192.5], ['x' => 262.5, 'y' => 175]];
  $truongSa = [['x' => 240, 'y' => 325], ['x' => 262.5, 'y' => 350], ['x' => 287.5, 'y' => 362.5], ['x' => 300, 'y' => 337.5], ['x' => 312.5, 'y' => 382.5], ['x' => 337.5, 'y' => 362.5], ['x' => 325, 'y' => 345], ['x' => 275, 'y' => 387.5]];
  $vnPath = config('vn_map.mainland');
  $islands = config('vn_map.islands');
@endphp
<section class="bg-forest-900 text-cream relative overflow-hidden">
  <div class="absolute inset-0 -z-0 opacity-10" style="background-image: radial-gradient(circle at 1px 1px, var(--color-cream) 1px, transparent 0); background-size: 28px 28px;"></div>
  <div class="container-x relative z-10 py-24 grid lg:grid-cols-12 gap-16 items-center">
    <div class="lg:col-span-5">
      <x-site.section-title :eyebrow="$locale === 'vi' ? 'Bản đồ cung ứng' : 'Supply network'" :title="__('site.home.map_title')" :lede="__('site.home.map_lede')" tone="dark" />
      <ul class="mt-10 grid grid-cols-2 gap-x-8 gap-y-4">
        @foreach ($markers as $m)
          <li class="flex items-start gap-3">
            <span class="mt-1.5 inline-block h-2 w-2 rounded-full bg-gold ring-4 ring-gold/20"></span>
            <div>
              <p class="text-sm font-semibold text-cream">{{ $m['l'] }}</p>
              <p class="text-xs text-cream/55">{{ $m['s'] }}</p>
            </div>
          </li>
        @endforeach
      </ul>
      <p class="mt-8 max-w-md text-xs text-cream/45 leading-relaxed">
        {{ $locale === 'vi' ? 'Bản đồ thể hiện mạng lưới đối tác và cảng xuất khẩu chính. Quần đảo Hoàng Sa và Trường Sa thuộc chủ quyền Việt Nam.' : 'The map shows partner regions and primary export ports. The Paracel and Spratly archipelagos are Vietnamese sovereign territory.' }}
      </p>
    </div>
    <div class="lg:col-span-7">
      <div class="mx-auto max-w-[640px]">
        <svg viewBox="0 0 400 440" class="w-full h-auto" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Vietnam supply network">
          <defs>
            <linearGradient id="vnFill" x1="0" y1="0" x2="0" y2="1">
              <stop offset="0%" stop-color="rgba(116, 198, 157, 0.18)"/>
              <stop offset="100%" stop-color="rgba(45, 154, 95, 0.10)"/>
            </linearGradient>
          </defs>
          <g opacity="0.08" stroke="rgba(201,169,110,0.6)" stroke-width="0.4">
            @for ($i = 0; $i < 9; $i++)
              <line x1="0" y1="{{ $i * 50 }}" x2="400" y2="{{ $i * 50 }}" />
              <line x1="{{ $i * 50 }}" y1="0" x2="{{ $i * 50 }}" y2="440" />
            @endfor
          </g>
          <path d="{{ $vnPath }}" fill="url(#vnFill)" stroke="rgba(199, 169, 110, 0.7)" stroke-width="1.2" stroke-linejoin="round"/>
          {{-- Phú Quốc, Côn Đảo, Cát Bà and a few Hạ Long islands — accurate
               Natural Earth coordinates, same fill+stroke as the mainland. --}}
          @foreach ($islands as $island)
            <path d="{{ $island['d'] }}" fill="url(#vnFill)" stroke="rgba(199, 169, 110, 0.7)" stroke-width="1" stroke-linejoin="round" data-island="{{ $island['name'] }}"/>
          @endforeach

          <g>
            @foreach ($hoangSa as $d)
              <circle cx="{{ $d['x'] }}" cy="{{ $d['y'] }}" r="1.4" fill="rgba(199,169,110,0.85)"/>
            @endforeach
            <circle cx="250" cy="184" r="22" fill="none" stroke="rgba(199,169,110,0.35)" stroke-width="0.8" stroke-dasharray="2 2"/>
            <text x="250" y="158" text-anchor="middle" font-size="9" font-weight="600" fill="rgba(250,247,242,0.8)" font-family="Inter, sans-serif">
              {{ $locale === 'vi' ? 'Q.Đ. HOÀNG SA' : 'PARACEL IS.' }}
            </text>
            <text x="250" y="170" text-anchor="middle" font-size="6.5" fill="rgba(250,247,242,0.5)" font-family="Inter, sans-serif">
              {{ $locale === 'vi' ? '(Việt Nam)' : '(Vietnam)' }}
            </text>
          </g>

          <g>
            @foreach ($truongSa as $d)
              <circle cx="{{ $d['x'] }}" cy="{{ $d['y'] }}" r="1.4" fill="rgba(199,169,110,0.85)"/>
            @endforeach
            <ellipse cx="290" cy="358" rx="58" ry="34" fill="none" stroke="rgba(199,169,110,0.35)" stroke-width="0.8" stroke-dasharray="2 2"/>
            <text x="290" y="305" text-anchor="middle" font-size="9" font-weight="600" fill="rgba(250,247,242,0.8)" font-family="Inter, sans-serif">
              {{ $locale === 'vi' ? 'Q.Đ. TRƯỜNG SA' : 'SPRATLY IS.' }}
            </text>
            <text x="290" y="317" text-anchor="middle" font-size="6.5" fill="rgba(250,247,242,0.5)" font-family="Inter, sans-serif">
              {{ $locale === 'vi' ? '(Việt Nam)' : '(Vietnam)' }}
            </text>
          </g>

          <text x="225" y="240" font-size="7" fill="rgba(250,247,242,0.25)" font-style="italic" font-family="Inter Variable, sans-serif" letter-spacing="2">
            {{ $locale === 'vi' ? 'BIỂN ĐÔNG' : 'EAST SEA' }}
          </text>

          @foreach ($markers as $m)
            <g transform="translate({{ $m['x'] }} {{ $m['y'] }})">
              <circle r="14" fill="rgba(201,169,110,0.10)">
                <animate attributeName="r" from="6" to="18" dur="2.4s" repeatCount="indefinite"/>
                <animate attributeName="opacity" from="0.6" to="0" dur="2.4s" repeatCount="indefinite"/>
              </circle>
              <circle r="3.8" fill="#c9a96e" stroke="#fff" stroke-width="1"/>
              <text x="6" y="2.5" font-size="8" font-weight="500" fill="#faf7f2" font-family="Inter, sans-serif" paint-order="stroke" stroke="rgba(15,54,35,0.85)" stroke-width="2.4">{{ $m['l'] }}</text>
            </g>
          @endforeach
        </svg>
      </div>
    </div>
  </div>
</section>
