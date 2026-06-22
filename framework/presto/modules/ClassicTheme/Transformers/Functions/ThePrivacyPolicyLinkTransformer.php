<?php

declare(strict_types=1);

namespace PrestoWorld\Modules\ClassicTheme\Transformers\Functions;

use PrestoWorld\Modules\ClassicTheme\Transformers\FunctionTransformer;

class ThePrivacyPolicyLinkTransformer extends FunctionTransformer
{
    public function handles(): string
    {
        return 'the_privacy_policy_link';
    }

    public function handle(mixed ...$args): mixed
    {
        $before = (string) ($args[0] ?? '');
        $after = (string) ($args[1] ?? '');

        echo $before . '<a href="/privacy-policy">Privacy Policy</a>' . $after;

        return null;
    }
}
