<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\SchemaOrg\Types;

use PrestoWorld\Modules\SchemaOrg\Schema;

class BreadcrumbList extends Schema
{
    public function __construct()
    {
        parent::__construct('BreadcrumbList');
    }

    /**
     * Set the list items.
     * Each item should be an associative array with:
     *   - '@type' => 'ListItem' (optional, we will set)
     *   - 'position' => integer (optional, we will set based on order)
     *   - 'name' => string
     *   - 'item' => string (URL) or null
     *
     * @param array $items List of items
     */
    public function setItems(array $items): void
    {
        $listItems = [];
        foreach ($items as $index => $item) {
            // Ensure we have name and item (url)
            if (!isset($item['name']) || !isset($item['item'])) {
                throw new \InvalidArgumentException('Each breadcrumb item must have a name and item (URL).');
            }
            $listItem = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item['name'],
                'item' => $item['item'],
            ];
            // Allow additional fields from $item
            $listItem = array_merge($listItem, $item);
            $listItems[] = $listItem;
        }
        $this->set('itemListElement', $listItems);
    }

    protected function validate(): void
    {
        $items = $this->get('itemListElement');
        if (!$items || !is_array($items) || count($items) === 0) {
            throw new \InvalidArgumentException('BreadcrumbList schema requires at least one itemListElement.');
        }
        foreach ($items as $index => $item) {
            if (!isset($item['@type']) || $item['@type'] !== 'ListItem') {
                throw new \InvalidArgumentException("Each item in itemListElement must have @type set to 'ListItem'.");
            }
            if (!isset($item['position']) || !is_int($item['position'])) {
                throw new \InvalidArgumentException("Each item must have an integer position.");
            }
            if (!isset($item['name']) || !is_string($item['name'])) {
                throw new \InvalidArgumentException("Each item must have a string name.");
            }
            if (!isset($item['item']) || (!is_string($item['item']) && $item['item'] !== null)) {
                throw new \InvalidArgumentException("Each item must have a string item (URL) or null.");
            }
        }
    }
}