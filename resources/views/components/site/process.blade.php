@props(['locale'])
@php
  $steps = $locale === 'vi' ? [
    ['n' => '01', 't' => 'Trao đổi nhu cầu', 'b' => 'Hai bên thảo luận chi tiết về sản phẩm, khối lượng, thị trường đích và yêu cầu chứng từ.'],
    ['n' => '02', 't' => 'Định hình spec', 'b' => 'Chốt chủng loại, chất lượng, bao bì, quy cách đóng gói và giá thành theo lô.'],
    ['n' => '03', 't' => 'MOU & làm mẫu', 'b' => 'Ký thoả thuận nguyên tắc, sản xuất mẫu chuẩn theo spec để khách duyệt (có hoàn tiền nếu mẫu không đạt).'],
    ['n' => '04', 't' => 'Duyệt mẫu', 'b' => 'Khách kiểm/cup mẫu và xác nhận. Mẫu đạt → bước tiếp; không đạt → ngưng và hoàn tạm ứng.'],
    ['n' => '05', 't' => 'Sản xuất & QC', 'b' => 'Tạm ứng theo hợp đồng, sản xuất hàng loạt với QC độc lập theo từng lô.'],
    ['n' => '06', 't' => 'Giao hàng & DDS', 'b' => 'Nghiệm thu, thanh toán cuối, giao hàng kèm bộ hồ sơ EUDR/FSC/CoA đầy đủ.'],
  ] : [
    ['n' => '01', 't' => 'Discovery call', 'b' => 'We exchange detailed information about products, volumes, target markets and documentation requirements.'],
    ['n' => '02', 't' => 'Spec definition', 'b' => 'Lock down kinds, quality, packaging, packing methods and per-lot pricing.'],
    ['n' => '03', 't' => 'MOU & sampling', 'b' => 'Sign a memorandum of understanding and produce samples to spec (with refund if samples fail to meet requirements).'],
    ['n' => '04', 't' => 'Sample approval', 'b' => 'Customer cups/tests and confirms. If approved we proceed; if not, we refund the deposit and stop.'],
    ['n' => '05', 't' => 'Production & QC', 'b' => 'Advance payment per contract, full production run with independent per-lot QC.'],
    ['n' => '06', 't' => 'Delivery & DDS', 'b' => 'Acceptance, final payment, shipment with EUDR / FSC / CoA documentation pack.'],
  ];
@endphp
<section class="relative bg-white/40">
  <div class="container-x py-24">
    <x-site.section-title
      :eyebrow="$locale === 'vi' ? 'Quy trình hợp tác' : 'How we work'"
      :title="$locale === 'vi' ? 'Sáu bước, từ trao đổi đến giao hàng' : 'Six steps, from intro call to delivered cargo'"
      :lede="$locale === 'vi' ? 'Quy trình OEM/ODM được chuẩn hóa, minh bạch và có cơ chế hoàn tiền nếu mẫu không đạt.' : 'A standardised OEM/ODM workflow with a refund mechanism if samples fail to meet your spec.'"
    />
    <ol class="mt-14 grid gap-px overflow-hidden rounded-3xl border border-earth-200/70 bg-earth-200/60 sm:grid-cols-2 lg:grid-cols-3">
      @foreach ($steps as $i => $s)
        <li class="relative bg-cream p-8">
          <div class="flex items-baseline gap-4">
            <span class="font-serif text-5xl font-medium text-forest-300/70">{{ $s['n'] }}</span>
            <h3 class="text-xl font-medium text-ink">{{ $s['t'] }}</h3>
          </div>
          <p class="mt-3 text-sm leading-relaxed text-ink/65">{{ $s['b'] }}</p>
        </li>
      @endforeach
    </ol>
  </div>
</section>
