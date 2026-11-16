<?php

namespace Database\Seeders;

use App\Models\Lead;
use App\Models\Partner;
use App\Models\Post;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAdmin();
        $this->seedSettings();
        $this->seedProducts();
        $this->seedPartners();
        $this->seedPosts();

        // Catalogue extensions — all idempotent (updateOrCreate), so
        // re-running `db:seed` never duplicates rows.
        $this->call([
            SocialSettingsSeeder::class,
            NavigationSettingsSeeder::class,
            ProductCategorySeeder::class,
            CoffeeProductSeeder::class,
            FaqSeeder::class,
        ]);
    }

    protected function seedAdmin(): void
    {
        $email = env('ADMIN_EMAIL') ?: 'admin@anduongiti.com';
        $password = env('ADMIN_PASSWORD');
        $user = User::firstOrNew(['email' => $email]);

        $user->fill([
            'name' => 'Administrator',
            // Mark seeded admin as verified so they can reach /admin.
            // Future users created through password-reset / invite flows
            // need to verify their email before they can sign in.
            'email_verified_at' => now(),
            'role' => User::ROLE_SUPER_ADMIN,
            'is_active' => true,
        ]);

        if (! $user->exists) {
            $user->password = filled($password) ? $password : Str::random(64);
        }

        $user->save();
    }

    protected function seedSettings(): void
    {
        Setting::write('contact', [
            'email' => 'contact@anduongiti.com',
            'phone' => '+84 915 663 683',
            'address_en' => '112 Me Tri Ha, Tu Liem Ward, Hanoi, Vietnam',
            'address_vi' => '112 Mễ Trì Hạ, Phường Từ Liêm, TP Hà Nội, Việt Nam',
            'linkedin' => 'https://www.linkedin.com/company/anduongiti',
        ]);

        Setting::write('stats', [
            'years_experience' => 5,
            'export_markets' => 12,
            'farming_households' => 1200,
            'partner_facilities' => 6,
        ]);
    }

    protected function seedProducts(): void
    {
        $rows = [
            [
                'product_key' => 'coffee', 'locale' => 'en',
                'title' => 'Coffee', 'eyebrow' => 'Instant OEM/ODM · Robusta · Arabica',
                'lede' => 'Custom-formula instant coffee — 3-in-1, 2-in-1, Black and freeze-dried — built for European, UK and SEA brands. Plus export-grade Robusta and Arabica green beans.',
                'specs' => [
                    ['Instant coffee', 'Spray-dried & freeze-dried, custom blends and packaging'],
                    ['Formats', '3-in-1 · 2-in-1 · Black · sticks · sachets · pouches · jars'],
                    ['Robusta', 'S16 / S18 / S18 Special — moisture ≤ 12.5%, defects ≤ 5/300g'],
                    ['Arabica washed', 'Sơn La & Lâm Đồng origin, screen 16+, SCA cup ≥ 82'],
                    ['Roast profile', 'Custom — Light to Full City, batch sizes from 5 kg'],
                    ['MOQ', '500 kg green / 5 kg roasted / 1 ton instant'],
                ],
                'origins' => ['Đắk Lắk · Buôn Ma Thuột', 'Sơn La', 'Lâm Đồng · Cầu Đất', 'Gia Lai'],
            ],
            [
                'product_key' => 'coffee', 'locale' => 'vi',
                'title' => 'Cà phê', 'eyebrow' => 'Hòa tan OEM/ODM · Robusta · Arabica',
                'lede' => 'Cà phê hòa tan theo công thức riêng — 3-in-1, 2-in-1, đen và freeze-dried — cho thương hiệu Anh, Châu Âu và Đông Nam Á. Cùng nhân Robusta và Arabica xuất khẩu.',
                'specs' => [
                    ['Hòa tan', 'Spray-dried & freeze-dried, blend & đóng gói theo yêu cầu'],
                    ['Format', '3-in-1 · 2-in-1 · đen · sticks · sachet · pouches · hũ'],
                    ['Robusta', 'S16 / S18 / S18 Special — độ ẩm ≤ 12.5%, lỗi ≤ 5/300g'],
                    ['Arabica rửa ướt', 'Vùng Sơn La & Lâm Đồng, sàng 16+, SCA cup ≥ 82'],
                    ['Profile rang', 'Tùy biến — Light đến Full City, mẻ từ 5 kg'],
                    ['MOQ', '500 kg nhân / 5 kg rang / 1 tấn hòa tan'],
                ],
                'origins' => ['Đắk Lắk · Buôn Ma Thuột', 'Sơn La', 'Lâm Đồng · Cầu Đất', 'Gia Lai'],
            ],
            [
                'product_key' => 'rubber', 'locale' => 'en',
                'title' => 'Natural rubber', 'eyebrow' => 'SVR 3L · 10 · CV60',
                'lede' => 'Vietnamese natural rubber for tyre and industrial applications — SVR 3L, 10 and CV60 grades. FSC chain-of-custody available on request.',
                'specs' => [
                    ['SVR 3L', 'Light colour, low impurities — premium tyre grade'],
                    ['SVR 10', 'General purpose, technically specified'],
                    ['SVR CV60', 'Constant viscosity 60 — high-end industrial applications'],
                    ['Packaging', "33.33 kg bales, 1.2 t pallets, 20 t / 40' container"],
                    ['Documentation', 'CoA per lot, FSC CoC and traceability on request'],
                    ['MOQ', "1 × 20' container"],
                ],
                'origins' => ['Central Highlands', 'Quảng Trị', 'Bình Phước', 'Đông Nam Bộ'],
            ],
            [
                'product_key' => 'rubber', 'locale' => 'vi',
                'title' => 'Cao su thiên nhiên', 'eyebrow' => 'SVR 3L · 10 · CV60',
                'lede' => 'Cao su thiên nhiên Việt Nam cho ngành lốp xe và công nghiệp — SVR 3L, 10 và CV60. Có FSC CoC khi đối tác yêu cầu.',
                'specs' => [
                    ['SVR 3L', 'Màu sáng, ít tạp — cao su lốp xe cao cấp'],
                    ['SVR 10', 'Đa dụng, theo tiêu chuẩn kỹ thuật'],
                    ['SVR CV60', 'Constant viscosity 60 — ứng dụng công nghiệp cao cấp'],
                    ['Đóng gói', "Bành 33.33 kg, pallet 1.2 t, 20 tấn / cont 40'"],
                    ['Hồ sơ', 'CoA từng lô, FSC CoC và truy xuất khi yêu cầu'],
                    ['MOQ', "1 × cont 20'"],
                ],
                'origins' => ['Tây Nguyên', 'Quảng Trị', 'Bình Phước', 'Đông Nam Bộ'],
            ],
        ];

        foreach ($rows as $row) {
            Product::updateOrCreate(
                ['product_key' => $row['product_key'], 'locale' => $row['locale']],
                $row,
            );
        }
    }

    protected function seedPartners(): void
    {
        $rows = [
            ['name' => 'An Thai Group', 'region' => 'Đắk Lắk · Buôn Ma Thuột',
             'spec_en' => 'Spray-dried instant coffee · FDA · KOSHER · HALAL',
             'spec_vi' => 'Cà phê hòa tan spray-dried · FDA · KOSHER · HALAL', 'sort_order' => 10],
            ['name' => 'PNL 2005 Vietnam', 'region' => 'TP.HCM · Bình Dương',
             'spec_en' => 'Coffee & tea OEM/ODM · 6 facilities · ISO 22000',
             'spec_vi' => 'OEM/ODM cà phê & trà · 6 nhà máy · ISO 22000', 'sort_order' => 20],
            ['name' => 'Highland Coop Network', 'region' => 'Sơn La · Lâm Đồng',
             'spec_en' => 'Specialty Arabica · 4C/UTZ co-ops',
             'spec_vi' => 'Arabica đặc sản · Hợp tác xã 4C/UTZ', 'sort_order' => 30],
            ['name' => 'Central Highlands Rubber', 'region' => 'Gia Lai · Đắk Nông',
             'spec_en' => 'SVR & RSS rubber · FSC FM available',
             'spec_vi' => 'Cao su SVR & RSS · FSC FM khả dụng', 'sort_order' => 40],
            ['name' => 'North-Central Logistics', 'region' => 'Hai Phong · Da Nang · Cat Lai',
             'spec_en' => 'Multimodal freight & CFS',
             'spec_vi' => 'Vận tải đa phương thức và CFS', 'sort_order' => 50],
        ];

        foreach ($rows as $row) {
            Partner::updateOrCreate(['name' => $row['name']], array_merge($row, ['visible' => true]));
        }
    }

    protected function seedPosts(): void
    {
        $rows = [
            [
                'slug' => 'eudr-readiness', 'locale' => 'en',
                'title' => 'Preparing your supply chain for EUDR enforcement',
                'excerpt' => 'Practical steps Vietnamese exporters and European importers can take today to be deforestation-due-diligence ready.',
                'tags' => ['EUDR', 'Compliance', 'Coffee'],
                'published_at' => '2026-04-22',
                'body' => "The EU Deforestation Regulation (EUDR) raises the bar for every commodity entering the bloc. For Vietnamese coffee and rubber exporters, the new rules mean more than paperwork — they require **plot-level traceability** and a verifiable supply chain story.\n\n## What \"deforestation-free\" actually means\n\nUnder EUDR, products must be produced on land that has **not been subject to deforestation after 31 December 2020**. This applies to coffee, cocoa, palm oil, soy, beef, wood and rubber — and any derivatives (instant coffee, leather, furniture).\n\n> The burden of proof sits with the EU operator, but the data must originate at the farm.\n\n## Three things we ask every supplier\n\n1. **GPS coordinates** of the plot of land — single-point for plots under 4 ha, polygon for larger.\n2. **Producer ID** linking each delivery back to a specific farmer or cooperative.\n3. **Risk assessment** with mitigation steps, refreshed annually.",
            ],
            [
                'slug' => 'eudr-readiness', 'locale' => 'vi',
                'title' => 'Chuẩn bị chuỗi cung ứng cho thực thi EUDR',
                'excerpt' => 'Các bước thiết thực mà nhà xuất khẩu Việt và nhà nhập khẩu châu Âu có thể làm ngay để sẵn sàng thẩm định không phá rừng.',
                'tags' => ['EUDR', 'Tuân thủ', 'Cà phê'],
                'published_at' => '2026-04-22',
                'body' => "Quy định không phá rừng của EU (EUDR) nâng tiêu chuẩn cho mọi mặt hàng nhập vào khối. Với nhà xuất khẩu cà phê và cao su Việt Nam, luật mới đòi hỏi không chỉ giấy tờ — mà là **truy xuất tới từng thửa đất** và câu chuyện chuỗi cung ứng có thể kiểm chứng.\n\n## \"Không phá rừng\" thực sự nghĩa là gì\n\nTheo EUDR, sản phẩm phải được sản xuất trên đất **không bị phá rừng sau ngày 31/12/2020**.",
            ],
            [
                'slug' => 'sourcing-arabica-son-la', 'locale' => 'en',
                'title' => "Sourcing specialty Arabica from Sơn La's high valleys",
                'excerpt' => "Why northern Vietnam's washed Arabica is winning shelf space with Italian and UK roasters.",
                'tags' => ['Arabica', 'Origin'],
                'published_at' => '2026-03-14',
                'body' => "Sơn La sits at 900–1,400 m elevation, with cool nights and limestone-rich soils — terroir that produces a clean, structured Arabica very different from Vietnam's more familiar Robusta.\n\n## Cup profile\n\n- **Body**: medium, silky\n- **Acidity**: bright, citric — orange peel and green apple\n- **Sweetness**: caramel, pomelo\n- **Finish**: long, slightly floral",
            ],
            [
                'slug' => 'sourcing-arabica-son-la', 'locale' => 'vi',
                'title' => 'Tìm nguồn Arabica đặc sản từ thung lũng cao Sơn La',
                'excerpt' => 'Vì sao Arabica rửa ướt miền Bắc Việt Nam đang chiếm vị trí trên kệ của các nhà rang xay Ý và Anh.',
                'tags' => ['Arabica', 'Vùng nguyên liệu'],
                'published_at' => '2026-03-14',
                'body' => "Sơn La nằm ở độ cao 900–1.400 m, đêm mát và thổ nhưỡng giàu đá vôi — terroir cho ra Arabica sạch, có cấu trúc, rất khác biệt với Robusta vốn quen thuộc của Việt Nam.",
            ],
            [
                'slug' => 'rubber-fsc-explained', 'locale' => 'en',
                'title' => 'FSC-certified natural rubber: what European buyers should look for',
                'excerpt' => 'A short primer on FSC Chain-of-Custody for natural rubber and what it changes for industrial buyers.',
                'tags' => ['Rubber', 'FSC', 'Sustainability'],
                'published_at' => '2026-02-08',
                'body' => "Forest Stewardship Council (FSC) certification has historically been associated with timber and paper. Since 2015, however, **FSC-certified natural rubber** has become a genuine differentiator for tyre manufacturers, glove producers and consumer-goods brands.",
            ],
            [
                'slug' => 'rubber-fsc-explained', 'locale' => 'vi',
                'title' => 'Cao su thiên nhiên FSC: điều người mua châu Âu cần lưu ý',
                'excerpt' => 'Tóm tắt nhanh về FSC Chain-of-Custody cho cao su thiên nhiên và ý nghĩa với buyer công nghiệp.',
                'tags' => ['Cao su', 'FSC', 'Bền vững'],
                'published_at' => '2026-02-08',
                'body' => "Chứng nhận Forest Stewardship Council (FSC) lâu nay gắn với gỗ và giấy. Nhưng từ 2015, **cao su thiên nhiên FSC** đã trở thành điểm khác biệt thật sự cho hãng lốp, nhà sản xuất găng tay và các thương hiệu hàng tiêu dùng.",
            ],
        ];

        foreach ($rows as $row) {
            Post::updateOrCreate(
                ['slug' => $row['slug'], 'locale' => $row['locale']],
                array_merge($row, ['status' => 'published']),
            );
        }
    }
}
