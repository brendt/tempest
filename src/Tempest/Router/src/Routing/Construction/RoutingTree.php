<?php

declare(strict_types=1);

namespace Tempest\Router\Routing\Construction;

use Tempest\Router\Routing\Matching\MatchingRegex;

/**
 * @internal
 */
final class RoutingTree
{
    /** @var array<string, RouteTreeNode> */
    private array $roots;

    public function __construct()
    {
        $this->roots = [];
    }

    public function add(MarkedRoute $markedRoute): void
    {
        $method = $markedRoute->route->method;

        // Find the root tree node based on HTTP method
        $node = $this->roots[$method->value] ??= RouteTreeNode::createRootRoute();

        // Traverse the tree and find the node for each route segment
        foreach ($markedRoute->route->split() as $routeSegment) {
            $node = $node->findOrCreateNodeForSegment($routeSegment);
        }

        $node->setTargetRoute($markedRoute);
    }

    /** @return array<string, MatchingRegex> */
    public function toMatchingRegexes(): array
    {
        return array_map(static fn (RouteTreeNode $node) => new RouteMatchingRegexBuilder($node)->toRegex(), $this->roots);
    }
}
