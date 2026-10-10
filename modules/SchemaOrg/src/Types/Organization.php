<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\SchemaOrg\Types;

use PrestoWorld\Modules\SchemaOrg\Schema;

class Organization extends Schema
{
    public function __construct()
    {
        parent::__construct('Organization');
    }

    public function setName(string $name): void
    {
        $this->set('name', $name);
    }

    public function setUrl(string $url): void
    {
        $this->set('url', $url);
    }

    public function setLogo(string $logo): void
    {
        $this->set('logo', $logo);
    }

    /**
     * Set sameAs array of URLs.
     */
    public function setSameAs(array $urls): void
    {
        $this->set('sameAs', $urls);
    }

    public function setContactPoint(array $contactPoint): void
    {
        $this->set('contactPoint', $contactPoint);
    }

    protected function validate(): void
    {
        if (!$this->get('name')) {
            throw new \InvalidArgumentException('Organization schema requires a name.');
        }
        // At least one of url, logo, sameAs, contactPoint recommended but not required.
    }
}