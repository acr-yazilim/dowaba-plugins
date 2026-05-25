<?php

declare(strict_types=1);

namespace Dowaba\LaravelBridge\Blade;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * <x-dowaba::contact-create-form :site-id="$siteId" action="/my/contacts/save" />
 *
 * Form POST yazılımcının kendi route'una gider. Controller'da:
 *   Dowaba::contacts()->create($siteId, $request->only(['name', 'phone', 'email']))
 *
 * Sadece HTML iskeleti üretir — yazılımcı kendi validation + dispatch'ini yazar.
 */
class ContactCreateFormComponent extends Component
{
    public function __construct(
        public int $siteId,
        public string $action,
        public string $method = 'POST',
        public string $class = 'dowaba-contact-form',
        public string $submitLabel = 'Kişi Ekle',
    ) {}

    public function render(): View
    {
        return view('dowaba::components.contact-create-form');
    }
}
