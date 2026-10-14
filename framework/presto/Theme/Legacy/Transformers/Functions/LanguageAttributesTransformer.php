<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class LanguageAttributesTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'language_attributes';
    }

    public function handle(mixed ...$args): mixed
    {
        echo 'lang="en-US"';
        return null;
    }
}
