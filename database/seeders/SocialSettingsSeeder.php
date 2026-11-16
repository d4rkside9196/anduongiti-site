<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SocialSettingsSeeder extends Seeder
{
    /**
     * Idempotent settings seeding:
     *  - `social`: Facebook / Shopee / Alibaba / LinkedIn links (link-only,
     *    CSP-safe — no embedded widgets anywhere on the site).
     *  - `contact`: merges `zalo` + `whatsapp` phone-format keys into the
     *    existing row without touching anything already configured.
     */
    public function run(): void
    {
        Setting::write('social', [
            'facebook' => 'https://www.facebook.com/anduongiti',
            'shopee' => 'https://shopee.vn/anduongiti',
            'alibaba' => 'https://anduongiti.en.alibaba.com',
            'linkedin' => 'https://www.linkedin.com/company/anduongiti',
        ]);

        $contact = Setting::read('contact', [
            'email' => 'contact@anduongiti.com',
            'phone' => '+84 915 663 683',
            'address_en' => '112 Me Tri Ha, Tu Liem Ward, Hanoi, Vietnam',
            'address_vi' => '112 Mễ Trì Hạ, Phường Từ Liêm, TP Hà Nội, Việt Nam',
            'linkedin' => 'https://www.linkedin.com/company/anduongiti',
        ]);

        // Merge — never overwrite values an admin has already tuned.
        Setting::write('contact', array_merge($contact, [
            'zalo' => $contact['zalo'] ?? '+84 915 663 683',
            'whatsapp' => $contact['whatsapp'] ?? '+84 915 663 683',
        ]));
    }
}
