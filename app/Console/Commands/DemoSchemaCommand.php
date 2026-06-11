<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Witals\Framework\Console\Command;
use PrestoWorld\Modules\Schema\PostTypeSchemaManager;

class DemoSchemaCommand extends Command
{
    protected string $name = 'demo:schema';
    protected string $description = 'Demonstrate PrestoWorld Live Migration for Taxonomies and Post Types';

    public function handle(array $args): int
    {
        $this->info('Starting PrestoWorld Schema Demo...');

        /** @var PostTypeSchemaManager $manager */
        $manager = app(PostTypeSchemaManager::class);

        // 1. Register Hierarchical Taxonomy with Custom Data
        $this->info('Registering Taxonomy: product_cat...');
        register_taxonomy('product_cat', ['product'], [
            'hierarchical' => true,
            'columns' => [
                'icon_url' => 'string',
                'bg_color' => 'string'
            ]
        ]);

        // 2. Register Post Type with Custom Data
        $this->info('Registering Post Type: product...');
        register_post_type('product', [
            'columns' => [
                'price' => 'decimal',
                'sku' => 'string',
                'stock' => 'integer'
            ]
        ]);

        $this->info('Migration Complete! Check your database.');
        $this->line('Tables created/synced:');
        $this->line('- pw_posts (Master Hub)');
        $this->line('- pw_post_product (Specialized Spoke)');
        $this->line('- pw_terms (Taxonomy Hub)');
        $this->line('- pw_tax_product_cat (Specialized Taxonomy)');
        $this->line('- pw_term_relationships (The Bridge)');

        return 0;
    }
}
