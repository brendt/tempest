<?php

declare(strict_types=1);

namespace Tempest\View\Enums;

/**
 * Represents the type of view.
 * Used to differentiate between raw and class views.
 */
enum ViewType: string
{
    case RAW = 'raw';
    case OBJECT = 'class';
}
