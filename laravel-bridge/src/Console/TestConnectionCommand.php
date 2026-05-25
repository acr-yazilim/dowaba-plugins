<?php

declare(strict_types=1);

namespace Dowaba\LaravelBridge\Console;

use Illuminate\Console\Command;

class TestConnectionCommand extends Command
{
    protected $signature = 'dowaba:test-connection';

    protected $description = 'Dowaba ile bağlantıyı test et (JWKS, token endpoint, /api/me)';

    public function handle(): int
    {
        $url = config('dowaba.url');

        $this->info("Dowaba URL: {$url}");
        $this->newLine();

        $this->warn('⚠  Bu komut iskelet — gerçek HTTP çağrıları sonraki seansta eklenecek.');
        $this->newLine();

        $this->line('  Sonraki seansta yapılacak:');
        $this->line('  • JWKS endpoint (.well-known/jwks.json) reachable mi?');
        $this->line('  • Token endpoint client_credentials kabul ediyor mu?');
        $this->line('  • /api/me 200 dönüyor mu?');

        return self::SUCCESS;
    }
}
