<?php

declare(strict_types=1);

namespace Tempest {
    use Tempest\View\GenericView;
    use Tempest\View\View;

    /**
     * Returns a {@see View} instance for the specified `$path`.
     */
    function view(string $path, mixed ...$params): View
    {
        return new GenericView($path)->data(...$params);
    }
}
