<?php

namespace Database\Seeders;

use App\Modules\Settings\Models\Page;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'title' => 'About Us',
                'content' => "Monaralk is a Sri Lankan car marketplace connecting buyers and sellers across the island.\n\nWe verify every listing, publish transparent pricing in LKR and support you from first enquiry through to handover.",
            ],
            [
                'title' => 'Contact Us',
                'content' => "Reach our team by phone or WhatsApp during business hours.\n\nPhone: +94 77 123 4567\nWhatsApp: +94 77 123 4567\nEmail: hello@monaralk.lk",
            ],
            [
                'title' => 'Terms of Service',
                'content' => 'These terms govern your use of Monaralk. Listings are provided by third-party sellers; Monaralk does not warrant vehicle condition unless explicitly stated.',
            ],
            [
                'title' => 'Privacy Policy',
                'content' => 'We collect only the information needed to process enquiries and listings, and we never sell your personal data.',
            ],
        ];

        foreach ($pages as $page) {
            Page::firstOrCreate(
                ['slug' => Str::slug($page['title'])],
                [
                    'title' => $page['title'],
                    'content' => $page['content'],
                    'meta_description' => mb_substr($page['content'], 0, 155),
                    'is_published' => true,
                ]
            );
        }
    }
}
