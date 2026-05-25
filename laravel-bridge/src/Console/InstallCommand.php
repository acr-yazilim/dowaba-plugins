<?php

declare(strict_types=1);

namespace Dowaba\LaravelBridge\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'dowaba:install {--force : Mevcut config dosyasını üzerine yaz}';

    protected $description = 'Dowaba Laravel Bridge interaktif kurulumu (config publish + .env satırları + migrate)';

    public function handle(): int
    {
        $this->info('Dowaba Laravel Bridge — Kurulum');
        $this->newLine();

        $this->warn('⚠  Bu komut iskelet — interaktif prompt akışı sonraki seansta eklenecek.');
        $this->newLine();

        $this->line('  Manuel kurulum şimdilik:');
        $this->line('  1. php artisan vendor:publish --tag=dowaba-config');
        $this->line('  2. .env dosyasına DOWABA_URL, DOWABA_CLIENT_ID, DOWABA_CLIENT_SECRET ekle');
        $this->line('  3. php artisan vendor:publish --tag=dowaba-migrations');
        $this->line('  4. php artisan migrate');
        $this->line('  5. php artisan dowaba:test-connection');

        return self::SUCCESS;
    }
}
