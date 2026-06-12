<?php

declare(strict_types=1);

namespace PrestoWorld\Foundation\Providers;

use Witals\Framework\Support\ServiceProvider;
use PrestoWorld\Foundation\Validator\Validator;
use PrestoWorld\Foundation\Validator\ValidatorInterface;

class ValidationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ValidatorInterface::class, function () {
            return new Validator();
        });

        $this->app->alias(ValidatorInterface::class, 'validator');
    }
}
