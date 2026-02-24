<?php

declare(strict_types=1);

namespace Modules\ReminderManager\Services;

use Witals\Framework\Container\Container;
use Cycle\Database\DatabaseProviderInterface;
use Cake\Chronos\Chronos;

class ReminderService
{
    protected DatabaseProviderInterface $dbal;

    public function __construct(Container $app)
    {
        $this->dbal = $app->make(DatabaseProviderInterface::class);
    }

    /**
     * Scan all services for upcoming expirations or warranty ends
     */
    public function scanAndNotify(): array
    {
        $stats = [
            'licenses' => 0,
            'domains' => 0,
            'hosting' => 0,
            'web_services' => 0,
        ];

        $stats['licenses'] = $this->scanLicenses();
        $stats['domains'] = $this->scanDomains();
        $stats['hosting'] = $this->scanHosting();
        $stats['web_services'] = $this->scanWebServices();

        return $stats;
    }

    protected function scanLicenses(): int
    {
        $count = 0;
        // Scans licenses expiring in the next 7 days
        $upcoming = $this->dbal->database()->select('*')
            ->from('optilarity_licenses')
            ->where('status', 'active')
            ->where('expires_at', '<=', Chronos::now()->addDays(7)->toDateTimeString())
            ->where('expires_at', '>', Chronos::now()->toDateTimeString())
            ->run()
            ->fetchAll();

        foreach ($upcoming as $license) {
            if ($this->shouldSendReminder($license['customer_id'], 'license', (int)$license['id'], 'expiration')) {
                $this->notify($license['customer_id'], 'license', $license, 'expiration');
                $count++;
            }
        }
        return $count;
    }

    protected function scanDomains(): int
    {
        $count = 0;
        $upcoming = $this->dbal->database()->select('*')
            ->from('optilarity_domains')
            ->where('status', 'active')
            ->where('expiry_date', '<=', Chronos::now()->addDays(30)->toDateTimeString())
            ->where('expiry_date', '>', Chronos::now()->toDateTimeString())
            ->run()
            ->fetchAll();

        foreach ($upcoming as $domain) {
            if ($this->shouldSendReminder($domain['customer_id'], 'domain', (int)$domain['id'], 'expiration')) {
                $this->notify($domain['customer_id'], 'domain', $domain, 'expiration');
                $count++;
            }
        }
        return $count;
    }

    protected function scanHosting(): int
    {
        $count = 0;
        $upcoming = $this->dbal->database()->select('*')
            ->from('optilarity_hostings')
            ->where('status', 'active')
            ->where('expiry_date', '<=', Chronos::now()->addDays(15)->toDateTimeString())
            ->where('expiry_date', '>', Chronos::now()->toDateTimeString())
            ->run()
            ->fetchAll();

        foreach ($upcoming as $hosting) {
            if ($this->shouldSendReminder($hosting['customer_id'], 'hosting', (int)$hosting['id'], 'expiration')) {
                $this->notify($hosting['customer_id'], 'hosting', $hosting, 'expiration');
                $count++;
            }
        }
        return $count;
    }

    protected function scanWebServices(): int
    {
        $count = 0;
        // Scan for warranty expiration
        $upcoming = $this->dbal->database()->select('*')
            ->from('optilarity_web_service_items')
            ->where('warranty_until', '<=', Chronos::now()->addDays(3)->toDateTimeString())
            ->where('warranty_until', '>', Chronos::now()->toDateTimeString())
            ->run()
            ->fetchAll();

        foreach ($upcoming as $item) {
            if ($this->shouldSendReminder($item['customer_id'], 'web_service', (int)$item['id'], 'warranty_end')) {
                $this->notify($item['customer_id'], 'web_service', $item, 'warranty_end');
                $count++;
            }
        }
        return $count;
    }

    /**
     * Check if a reminder has already been sent recently for this subject
     */
    protected function shouldSendReminder(int $customerId, string $type, int $id, string $event): bool
    {
        $last = $this->dbal->database()->select('sent_at')
            ->from('optilarity_reminder_logs')
            ->where('customer_id', $customerId)
            ->where('subject_type', $type)
            ->where('subject_id', $id)
            ->where('event_type', $event)
            ->orderBy('sent_at', 'DESC')
            ->run()
            ->fetch();

        if (!$last) return true;

        // Don't send more than once every 3 days for the same event
        return Chronos::parse($last['sent_at'])->addDays(3)->isPast();
    }

    /**
     * Send notification through user's enabled channels
     */
    protected function notify(int $customerId, string $subjectType, array $subjectData, string $eventType): void
    {
        $settings = $this->dbal->database()->select('*')
            ->from('optilarity_customer_notification_settings')
            ->where('customer_id', $customerId)
            ->where('is_enabled', true)
            ->run()
            ->fetchAll();

        // Fallback to email if no settings found
        if (empty($settings)) {
            $customer = $this->dbal->database()->select('email')->from('optilarity_customers')->where('id', $customerId)->run()->fetch();
            if ($customer) {
                $this->sendEmail($customer['email'], $subjectType, $subjectData, $eventType);
                $this->logReminder($customerId, $subjectType, (int)$subjectData['id'], $eventType, 'email');
            }
            return;
        }

        foreach ($settings as $setting) {
            $channel = $setting['channel'];
            $target = $setting['identifier'];

            switch ($channel) {
                case 'email':
                    $this->sendEmail($target, $subjectType, $subjectData, $eventType);
                    break;
                case 'telegram':
                    $this->sendTelegram($target, $subjectType, $subjectData, $eventType);
                    break;
                case 'sms':
                    $this->sendSMS($target, $subjectType, $subjectData, $eventType);
                    break;
            }
            
            $this->logReminder($customerId, $subjectType, (int)$subjectData['id'], $eventType, $channel);
        }
    }

    protected function logReminder(int $customerId, string $type, int $id, string $event, string $channel): void
    {
        $this->dbal->database()->insert('optilarity_reminder_logs')->values([
            'customer_id' => $customerId,
            'subject_type' => $type,
            'subject_id' => $id,
            'event_type' => $event,
            'channel' => $channel,
            'sent_at' => now()->toDateTimeString()
        ])->run();
    }

    protected function sendEmail(string $email, string $type, array $data, string $event): void
    {
        // Placeholder for real email logic
        error_log("Sending Email to {$email}: Your {$type} " . ($event === 'expiration' ? "is expiring" : "warranty is ending") . ".");
    }

    protected function sendTelegram(string $chatId, string $type, array $data, string $event): void
    {
        // Placeholder for real Telegram logic (calling existing Telegram service if any)
        error_log("Sending Telegram to {$chatId}: Your {$type} service needs attention.");
    }

    protected function sendSMS(string $phone, string $type, array $data, string $event): void
    {
        // Placeholder for real SMS logic
        error_log("Sending SMS to {$phone}: Service {$type} alert.");
    }
}
