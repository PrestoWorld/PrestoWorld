<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\SchemaOrg;

use PrestoWorld\Modules\SchemaOrg\Types\BreadcrumbList;
use PrestoWorld\Modules\SchemaOrg\Types\FAQPage;
use PrestoWorld\Modules\SchemaOrg\Types\Organization;
use PrestoWorld\Modules\SchemaOrg\Types\WebSite;

class SchemaFactory
{
    public function makeWebsite(): WebSite
    {
        return new WebSite();
    }

    public function makeOrganization(): Organization
    {
        return new Organization();
    }

    public function makeBreadcrumbList(): BreadcrumbList
    {
        return new BreadcrumbList();
    }

    public function makeFAQPage(): FAQPage
    {
        return new FAQPage();
    }

    /**
     * Create a schema instance by its type name.
     *
     * @param string $type Case-insensitive type name (website, organization, breadcrumblist, faqpage)
     * @return Schema
     * @throws \InvalidArgumentException
     */
    public function make(string $type): Schema
    {
        return match (strtolower($type)) {
            'website' => $this->makeWebsite(),
            'organization' => $this->makeOrganization(),
            'breadcrumblist', 'breadcrumblist' => $this->makeBreadcrumbList(),
            'faqpage' => $this->makeFAQPage(),
            default => throw new \InvalidArgumentException("Unknown schema type: {$type}"),
        };
    }
}