<?php

namespace Database\Seeders;

use App\Models\CoffeeProduct;
use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

class CoffeeProductSeeder extends Seeder
{
    /**
     * 8 catalogue products × 2 locales = 16 rows. Idempotent:
     * updateOrCreate on (slug, locale), so re-running refreshes content
     * without duplicating.
     */
    public function run(): void
    {
        foreach ($this->rows() as $row) {
            $categorySlug = $row['category'];
            unset($row['category']);

            $row['category_id'] = ProductCategory::query()
                ->where('slug', $categorySlug)
                ->where('locale', $row['locale'])
                ->firstOrFail()
                ->id;

            CoffeeProduct::updateOrCreate(
                ['slug' => $row['slug'], 'locale' => $row['locale']],
                $row,
            );
        }
    }

    private function rows(): array
    {
        $products = [
            'robusta-scratch-18-bean-coffee' => [
                'category' => 'beans',
                'bean_variety' => 'robusta',
                'sort_order' => 10,
                'image' => 'uploads/coffee/robusta-scratch-18-bean-coffee.jpg',
                'en' => [
                    'title' => 'Robusta Scratch 18 Bean Coffee',
                    'eyebrow' => 'Đắk Lắk · Single origin',
                    'description' => 'Export-grade wet-polished Robusta from Đắk Lắk family farms. Full body, dark chocolate and walnut notes, low acidity — the backbone of classic espresso blends and Vietnamese phin.',
                    'specs' => [
                        'Screen size: 18 (min. 90% above 16)',
                        'Moisture: ≤ 12.5%',
                        'Defects: ≤ 5 per 300 g (SCA black)',
                        'Caffeine: 2.0–2.4%',
                        'Origin: Đắk Lắk · Buôn Ma Thuột',
                        'Packaging: 60 kg jute bags, PP liner on request',
                        'MOQ: 500 kg (1 pallet) · 19 t per 20\' FCL',
                    ],
                ],
                'vi' => [
                    'title' => 'Cà phê nhân Robusta sàng 18',
                    'eyebrow' => 'Đắk Lắk · Single origin',
                    'description' => 'Robusta bóng nước xuất khẩu từ vùng nguyên liệu Đắk Lắk. Thân đậm, hậu vị chocolate đen và óc chó, ít chua — xương sống cho blend espresso và phin truyền thống Việt Nam.',
                    'specs' => [
                        'Sàng 18 (tối thiểu 90% trên sàng 16)',
                        'Độ ẩm: ≤ 12.5%',
                        'Lỗi: ≤ 5/300g (thuộc đen SCA)',
                        'Caffeine: 2.0–2.4%',
                        'Vùng nguyên liệu: Đắk Lắk · Buôn Ma Thuột',
                        'Đóng gói: bao jute 60 kg, tuỳ chọn lót PP',
                        'MOQ: 500 kg (1 pallet) · 19 tấn/cont 20\'',
                    ],
                ],
            ],
            'arabica-cau-dat-green-beans' => [
                'category' => 'beans',
                'bean_variety' => 'arabica',
                'sort_order' => 20,
                'image' => 'uploads/coffee/arabica-cau-dat-green-beans.jpg',
                'en' => [
                    'title' => 'Arabica Cầu Đất Green Beans',
                    'eyebrow' => 'Lâm Đồng · Washed',
                    'description' => 'Fully washed Arabica from the Cầu Đất plateau at 1,500 m. Bright citric acidity, caramel sweetness and a clean floral finish — cup score ≥ 82, lot traceability down to the cooperative.',
                    'specs' => [
                        'Screen size: 16 up',
                        'Moisture: ≤ 12.5%',
                        'Cup score: SCA ≥ 82',
                        'Process: Fully washed · raised beds',
                        'Origin: Lâm Đồng · Cầu Đất (1,500 m)',
                        'Packaging: 60 kg GrainPro-lined jute bags',
                        'MOQ: 300 kg (5 bags) · 19 t per 20\' FCL',
                    ],
                ],
                'vi' => [
                    'title' => 'Arabica nhân Cầu Đất',
                    'eyebrow' => 'Lâm Đồng · Rửa ướt',
                    'description' => 'Arabica rửa ướt hoàn toàn từ cao nguyên Cầu Đất, độ cao 1.500 m. Chua cam sáng, ngọt caramel, hậu hoa sạch — cup ≥ 82, truy xuất đến từng hợp tác xã.',
                    'specs' => [
                        'Sàng 16 trở lên',
                        'Độ ẩm: ≤ 12.5%',
                        'Chấm cup: SCA ≥ 82',
                        'Chế biến: Rửa ướt hoàn toàn · phơi nâng',
                        'Vùng nguyên liệu: Lâm Đồng · Cầu Đất (1.500 m)',
                        'Đóng gói: bao jute lót GrainPro 60 kg',
                        'MOQ: 300 kg (5 bao) · 19 tấn/cont 20\'',
                    ],
                ],
            ],
            'arabica-cau-dat-ground-250g' => [
                'category' => 'ground',
                'sort_order' => 30,
                'image' => 'uploads/coffee/arabica-cau-dat-ground-250g.jpg',
                'en' => [
                    'title' => 'Arabica Cầu Đất Ground 250 g',
                    'eyebrow' => 'Retail · Private label OK',
                    'description' => 'Roasted-to-order washed Arabica, ground for phin, pour-over or espresso on request. Nitrogen-flushed valve bags keep 12-month shelf life without additives — available under your private label.',
                    'specs' => [
                        'Roast: Medium (Light/Dark on request)',
                        'Grind: Phin · drip · espresso — as requested',
                        'Origin: 100% Lâm Đồng Arabica',
                        'Packaging: 250 g valve bag, N₂ flushed',
                        'Shelf life: 12 months',
                        'Private label: from 3,000 bags per design',
                        'MOQ: 1 carton sample · 100 bags wholesale',
                    ],
                ],
                'vi' => [
                    'title' => 'Arabica Cầu Đất xay 250 g',
                    'eyebrow' => 'Bán lẻ · Nhãn riêng',
                    'description' => 'Arabica rửa ướt rang theo đơn, xay chuẩn phin, pour-over hoặc espresso theo yêu cầu. Túi valve bơm nitơ giữ trọn hương 12 tháng, không phụ gia — nhận gia công nhãn riêng.',
                    'specs' => [
                        'Rang: Medium (Light/Dark theo yêu cầu)',
                        'Xay: Phin · drip · espresso — theo yêu cầu',
                        'Vùng nguyên liệu: 100% Arabica Lâm Đồng',
                        'Đóng gói: túi valve 250 g, bơm N₂',
                        'Hạn dùng: 12 tháng',
                        'Nhãn riêng: từ 3.000 túi/thiết kế',
                        'MOQ: 1 thùng mẫu · 100 túi bán sỉ',
                    ],
                ],
            ],
            'robusta-house-blend-ground-500g' => [
                'category' => 'ground',
                'sort_order' => 40,
                'image' => 'uploads/coffee/robusta-house-blend-ground-500g.jpg',
                'en' => [
                    'title' => 'House Blend Ground Coffee 500 g',
                    'eyebrow' => '70% Robusta · 30% Arabica',
                    'description' => 'Our export house blend — Robusta Đắk Lắk body with Arabica Sơn La aromatics, roasted dark for phin and milk coffee. The workhorse blend for cafés, offices and HoReCa importers.',
                    'specs' => [
                        'Blend: 70% Robusta Đắk Lắk · 30% Arabica Sơn La',
                        'Roast: Dark · roast date printed',
                        'Grind: Phin (coarse) · filter (fine) on request',
                        'Packaging: 500 g valve bag · 1 kg bulk bag',
                        'Caffeine: ~1.8%',
                        'MOQ: 50 kg · private label from 2,000 units',
                        'Container load: ~13 t per 20\' FCL',
                    ],
                ],
                'vi' => [
                    'title' => 'Cà phê xay House Blend 500 g',
                    'eyebrow' => '70% Robusta · 30% Arabica',
                    'description' => 'Blend xuất khẩu chủ lực của chúng tôi — thân Robusta Đắk Lắk hòa quyện hương Arabica Sơn La, rang đậm chuẩn phin và cà phê sữa. Lựa chọn quen thuộc của quán, văn phòng và nhà nhập khẩu HoReCa.',
                    'specs' => [
                        'Blend: 70% Robusta Đắk Lắk · 30% Arabica Sơn La',
                        'Rang: Đậm · ghi ngày rang',
                        'Xay: Phin (thô) · pha lọc (mịn) theo yêu cầu',
                        'Đóng gói: túi valve 500 g · túi lớn 1 kg',
                        'Caffeine: ~1.8%',
                        'MOQ: 50 kg · nhãn riêng từ 2.000 túi',
                        'Chở cont: ~13 tấn/cont 20\'',
                    ],
                ],
            ],
            'instant-3in1-16g-sticks' => [
                'category' => 'instant',
                'sort_order' => 50,
                'image' => 'uploads/coffee/instant-3in1-16g-sticks.jpg',
                'en' => [
                    'title' => 'Instant 3-in-1 Coffee 16 g × 20 Sticks',
                    'eyebrow' => 'OEM/ODM ready',
                    'description' => 'Creamy 3-in-1 from a certified OEM facility (ISO 22000, HACCP, FDA-registered). Custom sugar ratio, flavor and stick design under your brand — retail-ready cartons for importers and distributors.',
                    'specs' => [
                        'Format: 16 g stick × 20 sachets / box · 48 boxes / carton',
                        'Recipe: coffee 20% · non-dairy creamer · sugar (custom)',
                        'Certifications: ISO 22000 · HACCP · FDA · HALAL · KOSHER',
                        'Shelf life: 24 months',
                        'OEM: recipe, box art & language from 50,000 boxes',
                        'MOQ: 1 carton sample · 500 cartons wholesale',
                        'FOB: Ho Chi Minh City (Cát Lái)',
                    ],
                ],
                'vi' => [
                    'title' => 'Cà phê hòa tan 3-in-1 16 g × 20 gói',
                    'eyebrow' => 'Sẵn sàng OEM/ODM',
                    'description' => 'Cà phê 3-in-1 béo ngậy từ nhà máy OEM đạt chuẩn (ISO 22000, HACCP, đăng ký FDA). Tỷ lệ đường, hương vị và thiết kế gói theo thương hiệu của chị — thùng bán lẻ sẵn cho nhà nhập khẩu và phân phối.',
                    'specs' => [
                        'Format: gói 16 g × 20 gói/hộp · 48 hộp/thùng',
                        'Công thức: cà phê 20% · bột kem · đường (tuỳ chỉnh)',
                        'Chứng nhận: ISO 22000 · HACCP · FDA · HALAL · KOSHER',
                        'Hạn dùng: 24 tháng',
                        'OEM: công thức, thiết kế hộp & ngôn ngữ từ 50.000 hộp',
                        'MOQ: 1 thùng mẫu · 500 thùng bán sỉ',
                        'FOB: TP. Hồ Chí Minh (Cát Lái)',
                    ],
                ],
            ],
            'instant-black-2g-sticks' => [
                'category' => 'instant',
                'sort_order' => 60,
                'image' => 'uploads/coffee/instant-black-2g-sticks.jpg',
                'en' => [
                    'title' => 'Black Instant Coffee 2 g × 50 Sticks',
                    'eyebrow' => 'Sugar-free · Vending',
                    'description' => 'Spray-dried pure Robusta instant, zero sugar and zero additives. Office, vending and travel format — dissolves in hot or iced water in seconds with a strong, clean cup.',
                    'specs' => [
                        'Format: 2 g stick × 50 sticks / box · 100 boxes / carton',
                        'Coffee content: 100% Robusta spray-dried',
                        'Caffeine: ≥ 2.0% per serving basis',
                        'Additives: none — sugar free',
                        'Certifications: ISO 22000 · HACCP · FDA',
                        'Shelf life: 24 months',
                        'MOQ: 1 carton sample · 500 cartons wholesale',
                    ],
                ],
                'vi' => [
                    'title' => 'Cà phê đen hòa tan 2 g × 50 gói',
                    'eyebrow' => 'Không đường · Văn phòng',
                    'description' => 'Cà phê hòa tan Robusta spray-dried 100% nguyên chất, không đường không phụ gia. Dạng gói tiện cho văn phòng, máy bán hàng và du lịch — tan nhanh trong nước nóng lẫn đá, vị đậm sạch.',
                    'specs' => [
                        'Format: gói 2 g × 50 gói/hộp · 100 hộp/thùng',
                        'Nguyên liệu: 100% Robusta spray-dried',
                        'Caffeine: ≥ 2.0% theo phần serving',
                        'Phụ gia: không — không đường',
                        'Chứng nhận: ISO 22000 · HACCP · FDA',
                        'Hạn dùng: 24 tháng',
                        'MOQ: 1 thùng mẫu · 500 thùng bán sỉ',
                    ],
                ],
            ],
            'freeze-dried-black-coffee-100g' => [
                'category' => 'freeze_dried',
                'sort_order' => 70,
                'image' => 'uploads/coffee/freeze-dried-black-coffee-100g.jpg',
                'en' => [
                    'title' => 'Freeze-dried Black Coffee 100 g',
                    'eyebrow' => 'Premium · Aroma first',
                    'description' => 'Lyophilised Robusta–Arabica instant that keeps the aroma of fresh brew — chunky crystals, full body and a clean finish, in a resealable jar for premium retail shelves.',
                    'specs' => [
                        'Process: freeze-dried (lyophilised)',
                        'Blend: Robusta Đắk Lắk · Arabica Lâm Đồng',
                        'Caffeine: ~2.2%',
                        'Packaging: 100 g glass jar · resealable',
                        'Shelf life: 24 months',
                        'Certifications: ISO 22000 · HACCP · FDA · KOSHER',
                        'MOQ: 200 jars · private label from 5,000 jars',
                    ],
                ],
                'vi' => [
                    'title' => 'Cà phê đen sấy thăng hoa 100 g',
                    'eyebrow' => 'Cao cấp · Giữ hương',
                    'description' => 'Cà phê hòa tan sấy thăng hoa (lyophilised) giữ trọn hương của pha tươi — tinh thể to, thân đậm, hậu vị sạch. Hũ tái đóng kín cho kệ bán lẻ cao cấp.',
                    'specs' => [
                        'Chế biến: sấy thăng hoa (lyophilised)',
                        'Blend: Robusta Đắk Lắk · Arabica Lâm Đồng',
                        'Caffeine: ~2.2%',
                        'Đóng gói: hũ thủy tinh 100 g · đóng kín tái sử dụng',
                        'Hạn dùng: 24 tháng',
                        'Chứng nhận: ISO 22000 · HACCP · FDA · KOSHER',
                        'MOQ: 200 hũ · nhãn riêng từ 5.000 hũ',
                    ],
                ],
            ],
            'freeze-dried-premium-jar-80g' => [
                'category' => 'freeze_dried',
                'sort_order' => 80,
                'image' => 'uploads/coffee/freeze-dried-premium-jar-80g.jpg',
                'en' => [
                    'title' => 'Freeze-dried Premium Jar 80 g',
                    'eyebrow' => 'Single-origin Arabica',
                    'description' => 'Single-origin Cầu Đất Arabica, freeze-dried into a premium gift-ready jar. Delicate floral cup with caramel sweetness — our flagship private-label SKU for European retailers.',
                    'specs' => [
                        'Origin: 100% Arabica Lâm Đồng · Cầu Đất',
                        'Process: freeze-dried · low-temperature sublimation',
                        'Cup: floral · caramel · medium body',
                        'Packaging: 80 g jar + tamper seal · gift carton',
                        'Shelf life: 24 months',
                        'Private label: jar, label & carton fully customisable',
                        'MOQ: 3,000 jars per design',
                    ],
                ],
                'vi' => [
                    'title' => 'Hũ sấy thăng hoa cao cấp 80 g',
                    'eyebrow' => 'Arabica single-origin',
                    'description' => 'Arabica Cầu Đất single-origin sấy thăng hoa trong hũ quà tặng cao cấp. Vị hoa thanh lịch, ngọt caramel — SKU nhãn riêng chủ lực cho nhà bán lẻ Châu Âu.',
                    'specs' => [
                        'Vùng nguyên liệu: 100% Arabica Lâm Đồng · Cầu Đất',
                        'Chế biến: sấy thăng hoa · thăng hoa nhiệt độ thấp',
                        'Vị: hoa · caramel · thân vừa',
                        'Đóng gói: hũ 80 g + niêm phong · hộp quà',
                        'Hạn dùng: 24 tháng',
                        'Nhãn riêng: hũ, nhãn & hộp tuỳ biến hoàn toàn',
                        'MOQ: 3.000 hũ/thiết kế',
                    ],
                ],
            ],
        ];

        $rows = [];
        foreach ($products as $slug => $product) {
            foreach (['en', 'vi'] as $locale) {
                $rows[] = [
                    'slug' => $slug,
                    'locale' => $locale,
                    'category' => $product['category'],
                    'bean_variety' => $product['bean_variety'] ?? null,
                    'title' => $product[$locale]['title'],
                    'eyebrow' => $product[$locale]['eyebrow'],
                    'description' => $product[$locale]['description'],
                    'specs' => $product[$locale]['specs'],
                    'image' => $product['image'],
                    'sort_order' => $product['sort_order'],
                    'visible' => true,
                ];
            }
        }

        return $rows;
    }
}
