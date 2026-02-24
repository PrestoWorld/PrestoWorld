<?php

declare(strict_types=1);

namespace Modules\LicenseManager\Services;

class EnvatoService
{
    protected string $token;

    public function __construct()
    {
        $this->token = env('ENVATO_API_TOKEN', '');
    }

    /**
     * Get details of a purchase by its code
     */
    public function getPurchaseDetails(string $code): ?array
    {
        if (empty($this->token)) {
            return null;
        }

        $url = "https://api.envato.com/v3/market/buyer/purchase?code=" . urlencode($code);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $this->token,
            "User-Agent: Optilarity-License-Manager"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            return json_decode((string)$response, true);
        }

        return null;
    }

    /**
     * Check if a purchase code is valid
     */
    public function isValid(string $code): bool
    {
        return $this->getPurchaseDetails($code) !== null;
    }
}
