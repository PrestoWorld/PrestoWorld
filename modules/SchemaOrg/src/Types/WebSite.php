<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\SchemaOrg\Types;

use PrestoWorld\Modules\SchemaOrg\Schema;

class WebSite extends Schema
{
    public function __construct()
    {
        parent::__construct('WebSite');
    }

    /**
     * Set the URL of the website.
     */
    public function setUrl(string $url): void
    {
        $this->set('url', $url);
    }

    /**
     * Set the name of the website.
     */
    public function setName(string $name): void
    {
        $this->set('name', $name);
    }

    /**
     * Set the potentialAction (e.g., SearchAction).
     * @param array $potentialAction See https://schema.org/potentialAction
     */
    public function setPotentialAction(array $potentialAction): void
    {
        $this->set('potentialAction', $potentialAction);
    }

    protected function validate(): void
    {
        if (!$this->get('url')) {
            throw new \InvalidArgumentException('WebSite schema requires a url.');
        }
        if (!$this->get('name')) {
            throw new \InvalidArgumentException('WebSite schema requires a name.');
        }
        // Additional validation for potentialAction could be added here.
    }
}