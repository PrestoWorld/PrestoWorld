<?php

declare(strict_types=1);

namespace Modules\LicenseManager\Services;

use Witals\Framework\Container\Container;
use Cycle\Database\DatabaseProviderInterface;
use Cake\Chronos\Chronos;

class LicenseService
{
    protected DatabaseProviderInterface $dbal;

    public function __construct(Container $app)
    {
        $this->dbal = $app->make(DatabaseProviderInterface::class);
    }

    /**
     * Get or Generate RSA Private Key for a specific customer
     */
    public function getCustomerPrivateKey(int $customerId): ?string
    {
        $row = $this->dbal->database()->select('private_key')
            ->from('optilarity_user_keys')
            ->where('customer_id', $customerId)
            ->run()
            ->fetch();

        if ($row) {
            return $row['private_key'];
        }

        // Generate new key
        $keys = $this->generateKeyPair();
        $this->dbal->database()->insert('optilarity_user_keys')->values([
            'customer_id' => $customerId,
            'private_key' => $keys['private'],
            'created_at'  => now()->toDateTimeString()
        ])->run();

        return $keys['private'];
    }

    /**
     * Get induction of public key from a private key string
     */
    public function getPublicKeyFromPrivate(string $privateKey): ?string
    {
        $res = openssl_pkey_get_private($privateKey);
        if (!$res) return null;
        $details = openssl_pkey_get_details($res);
        return $details['key'] ?? null;
    }

    /**
     * Generate a new RSA Key Pair
     */
    protected function generateKeyPair(): array
    {
        $res = openssl_pkey_new([
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ]);
        
        openssl_pkey_export($res, $privateKey);
        $details = openssl_pkey_get_details($res);
        
        return [
            'private' => $privateKey,
            'public' => $details["key"]
        ];
    }

    /**
     * Decrypt data received from client
     */
    public function decryptData(string $encryptedData, string $licenseKey, string $email): ?array
    {
        $customerId = $this->getOrCreateCustomerId($email);
        $privateKey = $this->getCustomerPrivateKey($customerId);
        if (!$privateKey) return null;

        $decoded = base64_decode($encryptedData);
        if (openssl_private_decrypt($decoded, $decrypted, $privateKey)) {
            return json_decode($decrypted, true);
        }
        
        return null;
    }

    /**
     * Encrypt response for client
     */
    public function encryptResponse(array $data, string $email): string
    {
        $customerId = $this->getOrCreateCustomerId($email);
        $privateKey = $this->getCustomerPrivateKey($customerId);
        if (!$privateKey) return '';

        $json = json_encode($data);
        if (openssl_private_encrypt($json, $encrypted, $privateKey)) {
            return base64_encode($encrypted);
        }
        
        return '';
    }

    /**
     * Get or create customer ID by email
     */
    public function getOrCreateCustomerId(string $email): int
    {
        if (empty($email)) return 0;

        $customer = $this->dbal->database()->select('id')
            ->from('optilarity_customers')
            ->where('email', $email)
            ->run()
            ->fetch();

        if ($customer) {
            return (int)$customer['id'];
        }

        // Create a basic customer record if not exists
        return (int)$this->dbal->database()->insert('optilarity_customers')->values([
            'email' => $email,
            'first_name' => 'License',
            'last_name' => 'User',
            'status' => 'active',
            'created_at' => now()->toDateTimeString()
        ])->run();
    }

    /**
     * Verify license or membership
     * 
     * Supports both Domain (Web) and Machine ID (Desktop)
     */
    public function verify(string $key, string $email, ?string $identity = null, ?string $version = null, array $meta = []): array
    {
        // 1. Check for Active Membership first
        if ($this->checkMembership($email)) {
            return [
                'valid' => true,
                'license_type' => 'membership',
                'license_mode' => 'unlimited',
                'can_update' => true,
                'message' => 'Active membership found for ' . $email
            ];
        }

        if (empty($key)) {
            return ['valid' => false, 'message' => 'License key is required'];
        }

        // 2. Look up license in database
        $license = $this->dbal->database()->select('*')
            ->from('optilarity_licenses')
            ->where('license_key', $key)
            ->run()
            ->fetch();

        if (!$license) {
            // 3. Try registering as third-party if not found in local DB
            $tpResult = $this->verifyAndRegisterThirdParty($key, $email, $identity, $version);
            if ($tpResult['valid']) {
                 $license = $this->dbal->database()->select('*')
                    ->from('optilarity_licenses')
                    ->where('license_key', $key)
                    ->run()
                    ->fetch();
            } else {
                return $tpResult;
            }
        }

        // Log the activation attempt
        $this->logActivation($license['id'], $identity, $meta);

        // 4. Verify email
        if ($email && $license['email'] && strtolower($license['email']) !== strtolower($email)) {
             return ['valid' => false, 'message' => 'License email mismatch'];
        }

        // 5. Check status and expiry
        if ($license['status'] !== 'active') {
            return ['valid' => false, 'message' => 'License status: ' . $license['status']];
        }

        if ($license['expires_at'] && Chronos::parse($license['expires_at'])->isPast()) {
            return ['valid' => false, 'message' => 'License has expired'];
        }

        // 6. Activation logic (Strict vs Share) - Identity could be domain or machine_id
        $idResult = $this->handleIdentityActivation($license, $identity);
        if (!$idResult['success']) {
            return ['valid' => false, 'message' => $idResult['message']];
        }

        // 7. Update verification timestamp
        $this->dbal->database()->update('optilarity_licenses', [
            'last_verified_at' => now()->toDateTimeString()
        ], ['id' => $license['id']])->run();

        return [
            'valid' => true,
            'license_type' => $license['license_type'],
            'license_mode' => $license['license_mode'],
            'expires_at' => $license['expires_at'] ? Chronos::parse($license['expires_at'])->toDateString() : null,
            'can_update' => $this->canUpdate($license, $version)
        ];
    }

    /**
     * Log activation attempt to history table
     */
    protected function logActivation(int $licenseId, ?string $identity, array $meta = []): void
    {
        $this->dbal->database()->insert('optilarity_license_activations')->values([
            'license_id' => $licenseId,
            'domain' => $identity, // We use 'domain' column to store any identity (domain or machine id)
            'ip_address' => $meta['ip'] ?? null,
            'user_agent' => $meta['user_agent'] ?? null,
            'status' => 'success',
            'created_at' => now()->toDateTimeString()
        ])->run();
    }

    /**
     * Check if user has an active membership
     */
    protected function checkMembership(string $email): bool
    {
        if (empty($email)) return false;

        $customerId = $this->getOrCreateCustomerId($email);

        $membership = $this->dbal->database()->select('*')->from('optilarity_memberships')
            ->where('customer_id', $customerId)
            ->where('status', 'active')
            ->run()->fetch();

        if ($membership) {
            if (!$membership['end_date'] || Chronos::parse($membership['end_date'])->isFuture()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Handle identity (domain/machine) pairing and activation counts
     */
    protected function handleIdentityActivation(array $license, ?string $identity): array
    {
        if (!$identity) {
            return ['success' => true];
        }

        $identities = json_decode((string)($license['activated_domains'] ?? '[]'), true);
        if (!is_array($identities)) $identities = [];

        // Normalize if it looks like a domain, otherwise use as is (for machine IDs)
        $normalizedId = $this->isDomain($identity) ? $this->normalizeDomain($identity) : strtolower($identity);

        foreach ($identities as $id) {
             $checkId = $this->isDomain($id) ? $this->normalizeDomain($id) : strtolower($id);
             if ($checkId === $normalizedId) {
                 return ['success' => true];
             }
        }

        if ($license['license_mode'] === 'strict') {
            if (!empty($identities)) {
                return ['success' => false, 'message' => 'Strict license: already activated on ' . ($identities[0] ?? 'another machine')];
            }
        } else {
            if ($license['max_activations'] > 0 && $license['activations_used'] >= $license['max_activations']) {
                 return ['success' => false, 'message' => 'Maximum activations reached (' . $license['max_activations'] . ')'];
            }
        }

        $identities[] = $identity;
        $this->dbal->database()->update('optilarity_licenses', [
            'activated_domains' => json_encode($identities),
            'activations_used' => count($identities),
            'updated_at' => now()->toDateTimeString()
        ], ['id' => $license['id']])->run();

        return ['success' => true];
    }

    protected function isDomain(string $identity): bool
    {
        return str_contains($identity, '.') && !str_contains($identity, ' ');
    }

    protected function normalizeDomain(string $domain): string
    {
        return strtolower(trim(str_replace(['http://', 'https://', 'www.'], '', $domain), '/'));
    }

    protected function canUpdate(array $license, ?string $currentVersion): bool
    {
        if (!$license['expires_at']) return true;
        return Chronos::parse($license['expires_at'])->isFuture();
    }

    protected function verifyAndRegisterThirdParty(string $code, string $email, ?string $identity, ?string $version): array
    {
        if (empty($email)) {
            return ['valid' => false, 'message' => 'Email required to register third-party license'];
        }

        $envato = app(EnvatoService::class);
        $purchase = $envato->getPurchaseDetails($code);

        if (!$purchase) {
            return ['valid' => false, 'message' => 'Purchase code not found on third-party marketplace'];
        }

        $expiresAt = isset($purchase['supported_until']) ? Chronos::parse($purchase['supported_until'])->toDateTimeString() : null;
        $customerId = $this->getOrCreateCustomerId($email);

        $data = [
            'license_key' => $code,
            'license_type' => 'envato',
            'license_mode' => 'strict',
            'thirdparty_code' => $code,
            'email' => $email,
            'customer_id' => $customerId,
            'status' => 'active',
            'software_version' => $version,
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
            'expires_at' => $expiresAt,
        ];

        $licenseId = $this->dbal->database()->insert('optilarity_licenses')->values($data)->run();

        if ($identity) {
            $this->handleIdentityActivation(array_merge($data, ['id' => $licenseId]), $identity);
        }

        return [
            'valid' => true,
            'license_type' => 'envato',
            'message' => 'Envato license registered successfully',
            'expires_at' => $expiresAt ? Chronos::parse($expiresAt)->toDateString() : null,
            'can_update' => $expiresAt ? Chronos::parse($expiresAt)->isFuture() : true
        ];
    }
}
