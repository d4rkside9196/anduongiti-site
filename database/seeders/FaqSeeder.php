<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    /**
     * 6 FAQs per locale (12 rows), covering the trade questions buyers
     * actually ask: MOQ, incoterms, certifications, payment, samples and
     * OEM/private label. Certification claims stay consistent with the
     * x-site.certifications component (EUDR, FSC, BRC, IFS, ISO 22000,
     * HACCP, FDA, HALAL, KOSHER, 4C/UTZ). Idempotent via
     * updateOrCreate on (locale, category) — categories are a fixed
     * seeded set, so re-seeding after an admin edits a question updates
     * the row instead of duplicating it.
     */
    public function run(): void
    {
        foreach ($this->rows() as $row) {
            Faq::updateOrCreate(
                ['locale' => $row['locale'], 'category' => $row['category']],
                $row,
            );
        }
    }

    private function rows(): array
    {
        return [
            // ------------------------------------------------ EN ------------------------------------------------
            [
                'locale' => 'en', 'category' => 'orders', 'sort_order' => 10,
                'question' => 'What is your minimum order quantity (MOQ) for wholesale coffee export?',
                'answer' => 'Green beans: 500 kg per lot (about 19 tonnes fills a 20\' FCL). Instant coffee OEM/ODM: 500 cartons for our standard recipes, from 50,000 boxes for custom formulations. Freeze-dried retail jars: 3,000–5,000 units per design under private label. Rubber: 1 × 20\' container (about 19 tonnes of bales).',
            ],
            [
                'locale' => 'en', 'category' => 'shipping', 'sort_order' => 20,
                'question' => 'Which shipping terms and ports do you work with?',
                'answer' => 'We quote FOB and CIF as standard, and can arrange EXW or DAP on request. Coffee and instant lines ship from Ho Chi Minh City (Cát Lái) and rubber mostly via Hai Phong; Đà Nẵng is available for Central Highlands lots. Full export documentation — bill of lading, CoA, phytosanitary and origin certificates — is provided with every shipment.',
            ],
            [
                'locale' => 'en', 'category' => 'products', 'sort_order' => 30,
                'question' => 'What certifications do your partner facilities hold?',
                'answer' => 'Our partner facilities run under ISO 22000 and HACCP food-safety systems with U.S. FDA registration, plus HALAL and KOSHER certificates on the instant lines. BRC and IFS grades are available for European retail accounts. On the supply side we work with 4C/UTZ cooperatives, FSC-certified rubber plantations, and provide EUDR deforestation-free due-diligence documentation.',
            ],
            [
                'locale' => 'en', 'category' => 'payment', 'sort_order' => 40,
                'question' => 'Which payment terms do you accept?',
                'answer' => 'T/T is standard: 30% deposit on order confirmation and 70% against copy of shipping documents. Irrevocable L/C at sight is accepted for orders above USD 20,000. For long-term partners we can discuss a rolling open-account arrangement after the first three successful shipments.',
            ],
            [
                'locale' => 'en', 'category' => 'samples', 'sort_order' => 50,
                'question' => 'Can we get samples before committing to an order?',
                'answer' => 'Yes — we send green bean samples (250–500 g per grade) and instant/ground retail samples worldwide by courier. Samples are free of charge; you cover the courier fee or we deduct it from your first order. Roasted or custom-formulation samples take about 7–10 working days to prepare.',
            ],
            [
                'locale' => 'en', 'category' => 'oem', 'sort_order' => 60,
                'question' => 'Do you offer OEM / private label manufacturing?',
                'answer' => 'That is our specialty. Partner facilities in Bình Dương and TP.HCM produce 3-in-1, 2-in-1, black and freeze-dried instant coffee with your recipe, box design and language — MOQ from 50,000 boxes. We also do private-label ground coffee (from 2,000–3,000 units per design) and freeze-dried jars, including artwork, compliance and consolidation.',
            ],

            // ------------------------------------------------ VI ------------------------------------------------
            [
                'locale' => 'vi', 'category' => 'orders', 'sort_order' => 10,
                'question' => 'Số lượng đặt hàng tối thiểu (MOQ) cho cà phê xuất khẩu là bao nhiêu?',
                'answer' => 'Cà phê nhân: 500 kg/lô (khoảng 19 tấn đầy cont 20\'). Cà phê hòa tan OEM/ODM: 500 thùng với công thức chuẩn, từ 50.000 hộp với công thức riêng. Hũ sấy thăng hoa bán lẻ: 3.000–5.000 hũ/thiết kế nhãn riêng. Cao su: 1 × cont 20\' (khoảng 19 tấn bành).',
            ],
            [
                'locale' => 'vi', 'category' => 'shipping', 'sort_order' => 20,
                'question' => 'Quý công ty làm việc với những điều kiện vận chuyển và cảng nào?',
                'answer' => 'Chúng tôi báo giá chuẩn theo FOB và CIF, có thể sắp xếp EXW hoặc DAP theo yêu cầu. Cà phê và hòa tan xuất qua TP.HCM (Cát Lái), cao su chủ yếu qua Hải Phòng; Đà Nẵng phù hợp các lô Tây Nguyên. Bộ chứng từ xuất khẩu đầy đủ — vận đơn, CoA, chứng thư kiểm dịch và xuất xứ — đi kèm mọi lô hàng.',
            ],
            [
                'locale' => 'vi', 'category' => 'products', 'sort_order' => 30,
                'question' => 'Nhà máy đối tác có những chứng nhận nào?',
                'answer' => 'Nhà máy đối tác vận hành theo hệ thống an toàn thực phẩm ISO 22000 và HACCP, đăng ký FDA Hoa Kỳ, kèm chứng nhận HALAL và KOSHER trên dòng hòa tan. BRC và IFS sẵn sàng cho các kênh bán lẻ Châu Âu. Về vùng nguyên liệu, chúng tôi hợp tác với hợp tác xã 4C/UTZ, đồn điền cao su FSC và cung cấp bộ hồ sơ thẩm định không phá rừng EUDR.',
            ],
            [
                'locale' => 'vi', 'category' => 'payment', 'sort_order' => 40,
                'question' => 'Điều kiện thanh toán của quý công ty là gì?',
                'answer' => 'T/T là tiêu chuẩn: 30% đặt cọc khi xác nhận đơn, 70% theo bộ chứng từ vận tải. Nhận L/C nhìn thấy không hủy ngợi cho đơn trên 20.000 USD. Với đối tác dài hạn, chúng tôi có thể trao đổi cơ chế công nợ luân chuyển sau ba lô thành công đầu tiên.',
            ],
            [
                'locale' => 'vi', 'category' => 'samples', 'sort_order' => 50,
                'question' => 'Có thể nhận mẫu hàng trước khi đặt không?',
                'answer' => 'Có — chúng tôi gửi mẫu cà phê nhân (250–500 g/grade) và mẫu hòa tan/xay bán lẻ đi toàn thế giới qua chuyển phát. Mẫu miễn phí; quý khách chịu cước chuyển phát hoặc chúng tôi khấu trừ vào đơn đầu tiên. Mẫu rang hoặc công thức riêng mất khoảng 7–10 ngày làm việc để chuẩn bị.',
            ],
            [
                'locale' => 'vi', 'category' => 'oem', 'sort_order' => 60,
                'question' => 'Có nhận gia công OEM / nhãn riêng không?',
                'answer' => 'Đây là chuyên môn của chúng tôi. Nhà máy đối tác tại Bình Dương và TP.HCM sản xuất 3-in-1, 2-in-1, đen và sấy thăng hoa theo công thức, thiết kế hộp và ngôn ngữ của bạn — MOQ từ 50.000 hộp. Chúng tôi cũng gia công cà phê xay nhãn riêng (từ 2.000–3.000 túi/thiết kế) và hũ sấy thăng hoa, bao gồm thiết kế, tuân thủ chứng từ và gom hàng.',
            ],
        ];
    }
}
