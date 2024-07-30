<?php

namespace Kingsusuputih\LisensiSela;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class Sela
{
    private $sela_domain;
    private $sela_kode;
    private $bearer_token;

    const SELA_URL = 'http://new.sevenlight.id/api/lisensi-sela/cek';
    const TOKEN_URL = 'http://new.sevenlight.id/api/login';

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
        $client = app(Client::class);

        try {
            $response = $client->post(self::TOKEN_URL, [
                'json' => [
                    'email' => 'api@sevenlight.id',
                    'password' => '@Sevel2024',
                ],
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'verify' => true,
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
        echo view('lisensi-sela', ['pesan' => $message]);
        die;
    }
}
