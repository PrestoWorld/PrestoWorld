<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Foundation\Database\OptionRepository;
use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;
use Witals\Framework\Container\Container;

class GetOptionTransformer extends FunctionTransformer
{
    private ?OptionRepository $options = null;

    public function handles(): string
    {
        return 'get_option';
    }

    public function handle(mixed ...$args): mixed
    {
        $option = (string) ($args[0] ?? '');
        $default = $args[1] ?? false;

        $repo = $this->resolveRepository();
        if ($repo !== null && $repo->has($option)) {
            return $repo->get($option, $default);
        }

        $defaults = [
            'page_for_posts' => 0,
            'posts_per_page' => 10,
            'date_format' => 'F j, Y',
            'time_format' => 'g:i a',
            'blogname' => 'PrestoWorld',
            'blogdescription' => '',
            'siteurl' => '/',
            'home' => '/',
        ];

        return $defaults[$option] ?? $default;
    }

    private function resolveRepository(): ?OptionRepository
    {
        if ($this->options !== null) {
            return $this->options;
        }

        $app = Container::getInstance();
        if ($app === null || !$app->has(OptionRepository::class)) {
            return null;
        }

        /** @var OptionRepository */
        $repo = $app->make(OptionRepository::class);
        $this->options = $repo;
        return $repo;
    }
}
