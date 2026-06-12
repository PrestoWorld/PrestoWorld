<?php

declare(strict_types=1);

namespace PrestoWorld\Foundation\Providers;

use Witals\Framework\Support\ServiceProvider;
use PrestoWorld\Foundation\Serializer\Serializer;
use PrestoWorld\Foundation\Serializer\JsonSerializer;
use PrestoWorld\Foundation\Serializer\ArraySerializer;
use PrestoWorld\Foundation\Serializer\SerializerInterface;

class SerializerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(JsonSerializer::class, function () {
            return new JsonSerializer();
        });

        $this->app->singleton(ArraySerializer::class, function () {
            return new ArraySerializer();
        });

        $this->app->singleton(SerializerInterface::class, function ($app) {
            return new Serializer(
                $app->make(JsonSerializer::class),
                $app->make(ArraySerializer::class)
            );
        });

        $this->app->alias(SerializerInterface::class, 'serializer');
    }
}
