<?php

declare(strict_types=1);

namespace Modules\WebServices\Controllers;

use App\Http\Controllers\Controller;
use Witals\Framework\Http\Response;
use Witals\Framework\Http\Request;
use Cycle\Database\DatabaseProviderInterface;
use Cake\Chronos\Chronos;

class WebServiceController extends Controller
{
    protected DatabaseProviderInterface $dbal;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->dbal = $app->make(DatabaseProviderInterface::class);
    }

    /**
     * List all available services
     */
    public function index(): Response
    {
        $services = $this->dbal->database()->select('*')
            ->from('optilarity_web_services')
            ->where('status', 'active')
            ->run()
            ->fetchAll();

        return Response::json([
            'success' => true,
            'data' => $services
        ]);
    }

    /**
     * Submit a new service request/order
     */
    public function request(Request $request): Response
    {
        $data = $request->body();
        
        $email = $data['email'] ?? '';
        $serviceSlug = $data['service'] ?? '';
        $websiteUrl = $data['website_url'] ?? '';
        $notes = $data['notes'] ?? '';

        if (empty($email) || empty($serviceSlug)) {
            return Response::json(['success' => false, 'message' => 'Email and Service are required'], 400);
        }

        // Find service
        $service = $this->dbal->database()->select('*')
            ->from('optilarity_web_services')
            ->where('slug', $serviceSlug)
            ->run()
            ->fetch();

        if (!$service) {
            return Response::json(['success' => false, 'message' => 'Service not found'], 404);
        }

        $customerId = $this->getOrCreateCustomerId($email);

        // Calculate warranty until date
        $warrantyUntil = Chronos::now()->addDays($service['warranty_days'] ?? 30)->toDateTimeString();

        // Register the service item
        $itemId = $this->dbal->database()->insert('optilarity_web_service_items')->values([
            'customer_id'   => $customerId,
            'service_id'    => $service['id'],
            'website_url'   => $websiteUrl,
            'status'        => 'pending',
            'warranty_until'=> $warrantyUntil,
            'notes'         => $notes,
            'created_at'    => now()->toDateTimeString(),
            'updated_at'    => now()->toDateTimeString()
        ])->run();

        return Response::json([
            'success' => true,
            'message' => "Service '{$service['name']}' has been requested. We will contact you at {$email} shortly.",
            'item_id' => $itemId,
            'warranty_until' => $warrantyUntil
        ]);
    }

    /**
     * Seed initial services (Helper for internal use or migration)
     */
    public function seed(): Response
    {
        $services = [
            [
                'name' => 'Website Maintenance',
                'slug' => 'website-maintenance',
                'category' => 'maintenance',
                'description' => 'Bảo trì định kỳ, cập nhật core, plugin cho WordPress/Laravel.',
                'base_price' => 49.00,
                'warranty_days' => 30
            ],
            [
                'name' => 'Speed Optimization',
                'slug' => 'speed-optimization',
                'category' => 'optimization',
                'description' => 'Tăng tốc website toàn diện, tối ưu hóa database và tài nguyên.',
                'base_price' => 99.00,
                'warranty_days' => 60
            ],
            [
                'name' => 'Google PageSpeed Optimization',
                'slug' => 'pagespeed-optimization',
                'category' => 'optimization',
                'description' => 'Tối ưu hóa điểm số Google PageSpeed Insights (Mobile & Desktop).',
                'base_price' => 149.00,
                'warranty_days' => 90
            ],
            [
                'name' => 'Virus & Malware Removal',
                'slug' => 'virus-removal',
                'category' => 'security',
                'description' => 'Quét và diệt virus, mã độc, khôi phục website bị tấn công.',
                'base_price' => 79.00,
                'warranty_days' => 30
            ],
            [
                'name' => 'Vibe Code to Laravel/WP Conversion',
                'slug' => 'vibe-conversion',
                'category' => 'conversion',
                'description' => 'Chuyển đổi dự án Vibe Code thành website WordPress hoặc Laravel.',
                'base_price' => 499.00,
                'warranty_days' => 180
            ],
            [
                'name' => 'Web AI to Framework Migration',
                'slug' => 'ai-migration',
                'category' => 'migration',
                'description' => 'Di chuyển web dựa trên AI sang Laravel/WordPress đầy đủ chức năng.',
                'base_price' => 299.00,
                'warranty_days' => 180
            ],
        ];

        foreach ($services as $service) {
            $exists = $this->dbal->database()->select('id')
                ->from('optilarity_web_services')
                ->where('slug', $service['slug'])
                ->run()
                ->fetch();
            
            if (!$exists) {
                $service['created_at'] = now()->toDateTimeString();
                $this->dbal->database()->insert('optilarity_web_services')->values($service)->run();
            }
        }

        return Response::json(['success' => true, 'message' => 'Seeded ' . count($services) . ' services.']);
    }

    protected function getOrCreateCustomerId(string $email): int
    {
        $customer = $this->dbal->database()->select('id')
            ->from('optilarity_customers')
            ->where('email', $email)
            ->run()
            ->fetch();

        if ($customer) {
            return (int)$customer['id'];
        }

        return (int)$this->dbal->database()->insert('optilarity_customers')->values([
            'email' => $email,
            'first_name' => 'Service',
            'last_name' => 'Customer',
            'status' => 'active',
            'created_at' => now()->toDateTimeString()
        ])->run();
    }
}
