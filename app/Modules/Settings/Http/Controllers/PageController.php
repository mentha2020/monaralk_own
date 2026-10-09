<?php

namespace App\Modules\Settings\Http\Controllers;

use App\Modules\Settings\Models\Page;
use Illuminate\Contracts\View\View;

class PageController
{
    public function show(Page $page): View
    {
        abort_unless($page->is_published, 404);

        return view('pages.show', [
            'page' => $page,
        ]);
    }
}
