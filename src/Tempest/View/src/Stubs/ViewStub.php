<?php

declare(strict_types=1);

namespace Tempest\View\Stubs;

use Tempest\View\IsView;
use Tempest\View\View;

final class ViewStub implements View
{
    use IsView;

    public function __construct()
    {
        $this->path = __DIR__ . '/dummy.view.php';
    }
}
