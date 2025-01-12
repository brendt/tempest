<?php

declare(strict_types=1);

namespace Tempest\Router\Routing\Construction;

use Tempest\Router\Routing\Matching\MatchingRegex;

final readonly class RouteMatchingRegexBuilder
{
    // This limit is guesstimated using a small script with an ever in pattern feed into preg_match
    private const int PREG_REGEX_SIZE_LIMIT = 32768;

    private const int REGEX_SIZE_MARGIN = 256;

    private const int REGEX_SIZE_LIMIT = self::PREG_REGEX_SIZE_LIMIT - self::REGEX_SIZE_MARGIN;

    public function __construct(private RouteTreeNode $rootNode)
    {
    }

    public function toRegex(): MatchingRegex
    {
        // Holds all regex "chunks"
        $regexes = [];

        // Current regex chunk
        $regex = '';
        // Used to track how to 'end' a regex chunk partially in the building process
        $regexBack = '';

        /** @var (RouteTreeNode|null)[] $workingSet */
        $workingSet = [$this->rootNode];

        // Track how 'deep' we are in the tree to be able to rebuild the regex prefix when chunking
        /** @var RouteTreeNode[] $stack */
        $stack = [];

        // Processes the working set until it is empty
        while ($workingSet !== []) {
            // Use array_pop for performance reasons, this does mean that the working set works in a fifo order
            /** @var RouteTreeNode|null $node */
            $node = array_pop($workingSet);

            // null values are used as an end-marker, if one is found pop the stack and 'close' the regex
            if ($node === null) {
                array_pop($stack);
                $regex .= $regexBack[0];

                $regexBack = substr($regexBack, 1);

                continue;
            }

            // Checks if the regex is getting to big, and thus if we need to chunk it.
            if (strlen($regex) > self::REGEX_SIZE_LIMIT) {
                $regexes[] = '#' . substr($regex, 1) . $regexBack . '#';
                $regex = '';

                // Rebuild the regex match prefix based on the current visited parent nodes, known as 'the stack'
                foreach ($stack as $previousNode) {
                    $regex .= '|' . self::routeNodeSegmentRegex($previousNode);
                    $regex .= '(?';
                }
            }

            // Add the node route segment to the current regex
            $regex .= '|' . self::routeNodeSegmentRegex($node);
            $targetRouteRegex = self::routeNodeTargetRegex($node);

            // Check if node has children to ensure we only use branches if the node has children
            if ($node->dynamicPaths !== [] || $node->staticPaths !== []) {
                // The regex uses "Branch reset group" to match different available paths.
                // two available routes /a and /b will create the regex (?|a|b)
                $regex .= '(?';
                $regexBack .= ')';
                $stack[] = $node;

                // Add target route regex as an alteration group
                if ($targetRouteRegex) {
                    $regex .= '|' . $targetRouteRegex;
                }

                // Add an end marker to the working set, this will be processed after the children has been processed
                $workingSet[] = null;

                // Add dynamic routes to the working set, these will be processed before the end marker
                foreach ($node->dynamicPaths as $child) {
                    $workingSet[] = $child;
                }

                // Add static routes to the working set, these will be processed first due to the array_pop
                foreach ($node->staticPaths as $child) {
                    $workingSet[] = $child;
                }
            } else {
                // Add target route to main regex without any children
                $regex .= $targetRouteRegex;
            }
        }

        // Return all regex chunks including the current one
        return new MatchingRegex([
            ...$regexes,
            '#' . substr($regex, 1) . '#',
        ]);
    }

    /**
     * Create regex for the targetRoute in node with optional slash and end of line match `$`.
     * The `(*MARK:x)` is a marker which when this regex is matched will cause the matches array to contain
     *  a key `"MARK"` with value `"x"`, it is used to track which route has been matched.
     * Returns an empty string for nodes without a target.
     */
    private static function routeNodeTargetRegex(RouteTreeNode $node): string
    {
        if ($node->targetRoute === null) {
            return '';
        }

        return '\/?$(*' . MarkedRoute::REGEX_MARK_TOKEN . ':' . $node->targetRoute->mark . ')';
    }

    /**
     * Creates the regex for a route node's segment
     */
    private static function routeNodeSegmentRegex(RouteTreeNode $node): string
    {
        return match ($node->type) {
            RouteTreeNodeType::Root => '^',
            RouteTreeNodeType::Static => "/{$node->segment}",
            RouteTreeNodeType::Dynamic => '/(' . $node->segment . ')',
        };
    }
}
