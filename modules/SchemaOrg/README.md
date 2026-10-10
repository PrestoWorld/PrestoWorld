# SchemaOrg Module

This module provides a flexible, extensible way to generate Schema.org JSON-LD structured data for SEO and Core Web Vitals.

## Features

- **Object‑oriented schema builders**: Each Schema.org type is a class that extends `Schema`.
- **Validation**: Every schema type validates its required fields before rendering.
- **Central Registry**: A service (`schema-org.registry`) lets you register and render multiple schemas per request.
- **Factory**: A service (`schema-org.factory`) creates schema instances without manual `new`.
- **Easy to extend**: Add new types by extending the abstract `Schema` class.
- **JSON‑LD only**: Output is always valid JSON‑LD.

## Installation

The module is autoloaded via the PrestoWorld framework. Ensure the module is enabled in your configuration.

## Usage

### Via the PrestoWorld container

```php
// Get the registry and factory from the container
$registry = app('schema-org.registry');
$factory  = app('schema-org.factory');

// Create a schema using the factory
$website = $factory->makeWebsite();
$website->setUrl('https://example.com');
$website->setName('Example Site');

// Register it with a key
$registry->register('homepage_website', $website);

// Render a specific schema as a JSON‑LD script tag
echo $registry->render('homepage_website');
// Or render all registered schemas
echo $registry->renderAll();
```

### Using the factory directly

```php
$factory = app('schema-org.factory');

$organization = $factory->makeOrganization();
$organization->setName('Acme Corp');
$organization->setUrl('https://acme.example');
$organization->setLogo('https://acme.example/logo.png');

// Register and render...
```

### Available Types (via factory)

- `Website`  -> `$factory->makeWebsite()`
- `Organization` -> `$factory->makeOrganization()`
- `BreadcrumbList` -> `$factory->makeBreadcrumbList()`
- `FAQPage` -> `$factory->makeFAQPage()`

Or use the generic maker:

```php
$faq = $factory->make('faqpage');
```

### Creating a Custom Type

Extend the abstract `Schema` class and implement the `validate()` method.

```php
use PrestoWorld\Modules\SchemaOrg\Schema;

class Person extends Schema
{
    public function __construct()
    {
        parent::__construct('Person');
    }

    public function setName(string $name): void
    {
        $this->set('name', $name);
    }

    protected function validate(): void
    {
        if (!$this->get('name')) {
            throw new \InvalidArgumentException('Person schema requires a name.');
        }
    }
}
```

Then register it with the registry (you may also extend the factory if you want).

## Extending for Core Web Vitals

While this module focuses on Schema.org types, you can integrate Core Web Vitals by:

1. Adding a schema type for `WebPage` that includes `hasMeasurement` or `isRelatedTo` pointers to measurement data (requires extension schema).
2. Or, separately inject a script that measures vitals and sends to analytics (outside the scope of JSON‑LD).

## License

MIT