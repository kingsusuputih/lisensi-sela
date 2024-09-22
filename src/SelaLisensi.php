<?php

namespace Kingsusuputih\LisensiSela;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class SelaLisensi
{
    private $sela_domain;
    private $sela_kode;
    private $bearer_token;

    const SELA_URL = 'http://sevenlight.id/api/lisensi-sela/cek';
    const TOKEN_URL = 'http://sevenlight.id/api/login';

    public function __construct()
    {
        $this->sela_domain = $this->getSelaDomain();
        $this->sela_kode = env('SELA_KODE', 'weymalingpangsit');

        if (!$this->sela_domain || !$this->sela_kode) {
            return $this->renderError('Sela License details are not set correctly');
        }

        $this->bearer_token = $this->getBearerToken();
        if (!$this->bearer_token) {
            return $this->renderError('Failed to retrieve Bearer Token');
        }

        return $this->autoCheck();
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
