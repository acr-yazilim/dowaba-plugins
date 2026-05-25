<?php

declare(strict_types=1);

namespace Dowaba\LaravelBridge\Console;

use Dowaba\LaravelBridge\Auth\OauthFlow;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Console\Command;
use Throwable;

class TestConnectionCommand extends Command
{
    protected $signature = 'dowaba:test-connection {--verbose-output : Hata detaylarını yazdır}';

    protected $description = 'Dowaba ile bağlantıyı test et (JWKS, OIDC discovery, token endpoint sağlığı)';

    public function handle(OauthFlow $oauth): int
    {
        $url = rtrim(config('dowaba.url'), '/');
        $clientId = config('dowaba.client_id');

        $this->line(' Dowaba URL: <info>'.$url.'</info>');
        $this->line(' Client ID:  <info>'.($clientId ?: '<NOT SET>').'</info>');
        $this->newLine();

        $checks = [
            'OIDC Discovery' => fn () => $this->checkOidcDiscovery($url),
            'JWKS Endpoint' => fn () => $this->checkJwks($oauth),
            'Token Endpoint reachable' => fn () => $this->checkTokenEndpoint($url),
            'Config sanity' => fn () => $this->checkConfig(),
        ];

        $failed = 0;

        foreach ($checks as $name => $check) {
            try {
                $result = $check();
                $this->info(' ✓ '.str_pad($name, 28).$result);
            } catch (Throwable $e) {
                $failed++;
                $this->error(' ✗ '.str_pad($name, 28).$e->getMessage());

                if ($this->option('verbose-output')) {
                    $this->line('   '.$e::class.' @ '.$e->getFile().':'.$e->getLine());
                }
            }
        }

        $this->newLine();

        if ($failed === 0) {
            $this->info(" Tüm kontroller başarılı (".count($checks).'/'.count($checks).")");

            return self::SUCCESS;
        }

        $this->error(" {$failed} kontrol başarısız oldu — --verbose-output ile detayları görebilirsiniz.");

        return self::FAILURE;
    }

    private function checkOidcDiscovery(string $url): string
    {
        $client = $this->makeHttpClient();
        $response = $client->get($url.'/.well-known/openid-configuration');
        $status = $response->getStatusCode();

        if ($status !== 200) {
            throw new \RuntimeException("HTTP {$status}");
        }

        $data = json_decode((string) $response->getBody(), true);

        if (! is_array($data) || empty($data['issuer'])) {
            throw new \RuntimeException('issuer alanı boş');
        }

        return 'issuer='.$data['issuer'];
    }

    private function checkJwks(OauthFlow $oauth): string
    {
        $jwks = $oauth->fetchJwks(forceRefresh: true);
        $count = count($jwks['keys'] ?? []);

        if ($count === 0) {
            throw new \RuntimeException('keys boş');
        }

        return "{$count} key";
    }

    private function checkTokenEndpoint(string $url): string
    {
        $client = $this->makeHttpClient();
        $response = $client->post($url.'/api/oauth/token', ['form_params' => []]);
        $status = $response->getStatusCode();

        // Boş istek 400 dönmeli (parametre eksik). 200 dönerse şüpheli; 5xx fail.
        if ($status >= 500) {
            throw new \RuntimeException("HTTP {$status} (sunucu hatası)");
        }

        if ($status === 404) {
            throw new \RuntimeException('Endpoint bulunamadı (404)');
        }

        return "reachable (HTTP {$status})";
    }

    private function checkConfig(): string
    {
        $missing = [];

        if (! config('dowaba.url')) {
            $missing[] = 'DOWABA_URL';
        }

        if (! config('dowaba.client_id')) {
            $missing[] = 'DOWABA_CLIENT_ID';
        }

        if (! config('dowaba.client_secret')) {
            $missing[] = 'DOWABA_CLIENT_SECRET';
        }

        if (! config('dowaba.redirect_uri')) {
            $missing[] = 'DOWABA_REDIRECT_URI';
        }

        if (! empty($missing)) {
            throw new \RuntimeException('eksik env: '.implode(', ', $missing));
        }

        return 'tüm zorunlu env\'ler dolu';
    }

    private function makeHttpClient(): Client
    {
        return new Client([
            'timeout' => (int) config('dowaba.http.timeout', 10),
            'connect_timeout' => (int) config('dowaba.http.connect_timeout', 3),
            'http_errors' => false,
        ]);
    }
}
