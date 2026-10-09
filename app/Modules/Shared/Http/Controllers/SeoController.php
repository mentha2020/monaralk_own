<?php

namespace App\Modules\Shared\Http\Controllers;

use App\Modules\Catalog\Models\Vehicle;
use App\Modules\Settings\Models\Page;
use Illuminate\Http\Response;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class SeoController
{
    public function sitemap(): Response
    {
        $sitemap = Sitemap::create()
            ->add((new Url(route('home')))->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)->setPriority(1.0))
            ->add((new Url(route('vehicles.index')))->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)->setPriority(0.9))
            ->add((new Url(route('contact')))->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)->setPriority(0.6))
            ->add((new Url(route('submit.create')))->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)->setPriority(0.6));

        Vehicle::query()
            ->published()
            ->latest('published_at')
            ->get(['id', 'slug', 'published_at', 'updated_at'])
            ->each(function (Vehicle $vehicle) use ($sitemap) {
                $sitemap->add(
                    (new Url(route('vehicles.show', $vehicle)))
                        ->setLastModificationDate($vehicle->updated_at)
                        ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                        ->setPriority(0.8)
                );
            });

        Page::query()
            ->published()
            ->latest('updated_at')
            ->get(['id', 'slug', 'updated_at'])
            ->each(function (Page $page) use ($sitemap) {
                $sitemap->add(
                    (new Url(route('pages.show', $page)))
                        ->setLastModificationDate($page->updated_at)
                        ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                        ->setPriority(0.4)
                );
            });

        return response($sitemap->render(), 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /dashboard',
            'Disallow: /profile',
            'Disallow: /favourites',
            'Disallow: /compare',
            'Disallow: /saved/',
            'Disallow: /language/',
            'Allow: /$',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
