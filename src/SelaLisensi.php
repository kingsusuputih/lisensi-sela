<?php

namespace Kingsusuputih\LisensiSela;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SelaLisensi
{
    private $sela_domain;
    private $sela_kode;
    private $bearer_token;

    const SELA_URL = 'http://sevenlight.id/api/lisensi-sela/cek';
    const TOKEN_URL = 'http://sevenlight.id/api/login';

    // Cache durations
    const TOKEN_CACHE_MINUTES = 55; // Token valid 1 jam, cache 55 menit
    const LICENSE_CACHE_MINUTES = 1440; // Cache license check 24 jam
    const QUICK_CACHE_MINUTES = 5; // Quick cache untuk error/failed states

    public function __construct()
    {
        $this->sela_domain = $this->getSelaDomain();
        $this->sela_kode = env('SELA_KODE', 'weymalingpangsit');

        if (!$this->sela_domain || !$this->sela_kode) {
            return $this->renderError('Sela License details are not set correctly');
        }

        // Quick check dari cache terlebih dahulu
        $cacheKey = "sela_license_check_{$this->sela_domain}_{$this->sela_kode}";

        $cachedResult = Cache::get($cacheKey);
        if ($cachedResult === 'valid') {
            return true; // License valid dari cache
        }

        if ($cachedResult === 'invalid') {
            return $this->renderError('License validation failed (cached)');
        }

        // Jika belum ada di cache, lakukan pengecekan
        return $this->performLicenseCheck();
    }

    private function performLicenseCheck()
    {
        // Coba ambil token dari cache dulu
        $this->bearer_token = $this->getCachedBearerToken();

        if (!$this->bearer_token) {
            // Cache failed state untuk beberapa menit
            $this->cacheResult('failed', self::QUICK_CACHE_MINUTES);
            return true; // Allow access jika service down
        }

        return $this->autoCheckWithCache();
    }

    private function getCachedBearerToken()
    {
        $tokenCacheKey = 'sela_bearer_token';

        return Cache::remember($tokenCacheKey, self::TOKEN_CACHE_MINUTES, function () {
            return $this->getBearerTokenOptimized();
        });
    }

    private function getBearerTokenOptimized()
    {
        try {
            // Gunakan Laravel HTTP Client dengan timeout yang reasonable
            $response = Http::timeout(5)
                ->acceptJson()
                ->asForm()
                ->post(self::TOKEN_URL, [
                    'email' => 'api@sevenlight.id',
                    'password' => '@Sevel2024',
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['access_token'] ?? null;
            }

            Log::warning('Failed to get bearer token', ['status' => $response->status()]);
            return null;
        } catch (\Exception $e) {
            Log::error('Bearer token request failed: ' . $e->getMessage());
            return null;
        }
    }

    private function autoCheckWithCache()
    {
        try {
            $response = Http::timeout(5)
                ->withToken($this->bearer_token)
                ->acceptJson()
                ->post(self::SELA_URL, [
                    'domain' => $this->sela_domain,
                    'kode' => $this->sela_kode,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['status']) && $data['status'] == 'error') {
                    $this->cacheResult('invalid', self::LICENSE_CACHE_MINUTES);
                    return $this->renderError($data['pesan']);
                }

                // License valid, cache result
                $this->cacheResult('valid', self::LICENSE_CACHE_MINUTES);
                return true;
            } else {
                // Service error, cache failed state sebentar saja
                $this->cacheResult('failed', self::QUICK_CACHE_MINUTES);
                return true; // Allow access jika service bermasalah
            }
        } catch (\Exception $e) {
            Log::error('License check failed: ' . $e->getMessage());
            // Cache failed state dan allow access
            $this->cacheResult('failed', self::QUICK_CACHE_MINUTES);
            return true;
        }
    }

    private function cacheResult($status, $minutes)
    {
        $cacheKey = "sela_license_check_{$this->sela_domain}_{$this->sela_kode}";
        Cache::put($cacheKey, $status, now()->addMinutes($minutes));
    }

    private function getSelaDomain()
    {
        if (isset($_SERVER['HTTP_HOST'])) {
            $host = $_SERVER['HTTP_HOST'];
            return (substr($host, 0, 4) === "www.") ? substr($host, 4) : $host;
        }

        // Gunakan environment variable atau konfigurasi jika HTTP_HOST tidak tersedia
        return env('SELA_DOMAIN', 'localhost');
    }

    private function getBearerToken()
    {
        return $this->getBearerTokenByCurl();
    }

    private function getBearerTokenByCurl()
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => self::TOKEN_URL,
            // CURLOPT_SSL_VERIFYPEER => false,  // Disable SSL verification (gak disarankan di production)
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'email' => 'api@sevenlight.id',
                'password' => '@Sevel2024',
            ]),
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                'Content-Type: application/x-www-form-urlencoded',
            ),
        ));

        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            Log::error('cURL error: ' . curl_error($curl));
            curl_close($curl);
            return null;
        }

        curl_close($curl);

        $data = json_decode($response, true);
        return $data['access_token'] ?? null;
    }

    private function getBearerTokenByGuzzle()
    {
        $client = app(Client::class);

        try {
            $response = $client->post(self::TOKEN_URL, [
                'form_params' => [
                    'email' => 'api@sevenlight.id',
                    'password' => '@Sevel2024',
                ],
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'verify' => false,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return $data['access_token'] ?? null;
        } catch (\Exception $e) {
            Log::error('Failed to retrieve Bearer Token: ' . $e->getMessage());
            return null;
        }
    }

    private function autoCheck()
    {
        return $this->autoCheckByCurl();
    }

    private function autoCheckByCurl()
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => self::SELA_URL,
            // CURLOPT_SSL_VERIFYPEER => false,  // Disable SSL verification (gak disarankan di production)
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'domain' => $this->sela_domain,
                'kode' => $this->sela_kode,
            ]),
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $this->bearer_token,
                'Accept: application/json',
                'Content-Type: application/json',
            ),
        ));

        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            Log::error('cURL error: ' . curl_error($curl));
            curl_close($curl);
            return $this->renderError('Sela CMS License Check Failed');
        }

        curl_close($curl);

        $data = json_decode($response, true);

        if (isset($data['status']) && $data['status'] == 'error') {
            return $this->renderError($data['pesan']);
        }
    }

    private function autoCheckByGuzzle()
    {
        $client = app(Client::class);
        $params = [
            'domain' => $this->sela_domain,
            'kode' => $this->sela_kode,
        ];

        try {
            $response = $client->post(self::SELA_URL, [
                'json' => $params,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->bearer_token,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'verify' => true,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (isset($data['status']) && $data['status'] == 'error') {
                return $this->renderError($data['pesan']);
            }
        } catch (\Exception $e) {
            Log::error('Sela CMS License Check Failed: ' . $e->getMessage());
            return $this->renderError('Sela CMS License Check Failed');
        }
    }

    private function renderError($message)
    {
        echo view('LisensiSela::lisensi-sela-' . config('sela-lisensi.version'), ['pesan' => $message]);
        die;
    }
}
