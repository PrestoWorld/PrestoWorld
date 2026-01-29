<?php

/**
 * User-defined Transformers Configuration
 * 
 * This file allows users to:
 * 1. Enable/disable specific transformers
 * 2. Add custom transformers
 * 3. Override plugin-provided transformers
 */

return [
    'transformers' => [
        // Example: Custom transformer
        [
            'id' => 'my_custom_global_fix',
            'class' => \App\Transformers\MyCustomGlobalTransformer::class,
            'keywords' => ['my_global_var'],
            'enabled' => true
        ],
        
        // Example: Disable a core transformer
        [
            'id' => 'output_buffer',
            'enabled' => false // This will disable the core output buffer transformer
        ],
    ]
];
