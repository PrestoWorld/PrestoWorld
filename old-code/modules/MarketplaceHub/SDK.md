# PrestoWorld Marketplace SDK (Platform-in-a-Box)

This SDK is a blueprint and set of core interfaces designed to help 3rd-party developers build their own **PrestoWorld Marketplace Platform**. By implementing these patterns, your platform will be immediately compatible with all PrestoWorld installations.

## 1. Architectural Design Patterns

This SDK follows a strictly decoupled architecture:

- **Strategy Pattern (Providers)**: Allows your platform to pull data from any source (SQL, GitHub, S3) without changing the API logic.
- **Transformer Pattern**: Decouples your internal database schemas from the public-facing PrestoWorld JSON API specification.
- **Factory Pattern**: Standardizes the creation of Theme and Plugin entities across the platform.
- **Adapter Pattern**: Handles various storage backends for the physical `.zip` downloads.

---

## 2. Core Components (The Skeleton)

### A. The Data Provider (`MarketplaceProviderInterface`)
You must implement this interface to tell the SDK where your themes and plugins are stored.

```php
interface MarketplaceProviderInterface {
    public function findBySlug(string $slug): ?ExtensionEntity;
    public function search(array $filters): ExtensionCollection;
}
```

### B. The Metadata Transformer
Maps your internal objects to the PrestoWorld JSON format.

```php
class PrestoV1Transformer {
    public function transform(ExtensionEntity $entity): array {
        return [
            "slug" => $entity->getSlug(),
            "name" => $entity->getName(),
            "download_url" => $this->signUrl($entity->getFile())
        ];
    }
}
```

---

## 3. API Hub Specification (Endpoint Structure)

To be a valid PrestoWorld Hub, your platform MUST expose these endpoints:

1.  `GET /api/v1/catalog`: The main discovery endpoint.
2.  `GET /api/v1/info/{slug}`: Detailed metadata for a single item.
3.  `GET /api/v1/download/{slug}/{version}`: The secure download link.

---

## 4. Building Your Own Platform (Quick Start)

1.  **FORK** this `MarketplaceSDK` module.
2.  **IMPLEMENT** a `DatabaseProvider` to connect to your theme/plugin inventory.
3.  **CONFIGURE** the `DownloadAdapter` to point to your S3 or secure local storage.
4.  **DEPLOY** and provide your Hub URL (e.g., `https://my-marketplace.com/api/v1`) to your users.

Users can then simply add your URL to their **PrestoWorld Dashboard** to start using your themes and plugins.
