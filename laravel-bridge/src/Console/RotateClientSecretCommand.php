<?php

declare(strict_types=1);

namespace Dowaba\LaravelBridge\Console;

use Illuminate\Console\Command;

class RotateClientSecretCommand extends Command
{
    protected $signature = 'dowaba:rotate-client-secret';

    protected $description = 'Dowaba OAuth client secret\'ini rotate et (Dowaba admin paneli üzerinden tetikler)';

    public function handle(): int
    {
        $this->warn('⚠  Bu komut iskelet — sonraki seansta Dowaba /api/oauth/clients/{id}/rotate-secret endpoint\'i ile entegre edilecek.');
        $this->newLine();

        $this->line('  Şimdilik manuel:');
        $this->line('  Dowaba admin → /admin/oauth/clients → ilgili client → "Secret\'i Yenile" butonu');
        $this->line('  Yeni secret\'i .env\'deki DOWABA_CLIENT_SECRET\'a kopyala, php artisan config:clear');

        return self::SUCCESS;
    }
}
