<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\routing;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * Contains list of available routes.
 *
 * @since  5.4.0
 * @implements  \IteratorAggregate<Route>
 */
class Routes implements IteratorAggregate
{
    /** @var  Route[] */
    private array $routes = [];

    public function add(Route $route): Route
    {
        $this->routes[] = $route;
        return $route;
    }

    /**
     * finds route based on called uri
     */
    public function match(CalledUri $calledUri): MatchingRoutes
    {
        $allowedMethods = [];
        $matching       = [];
        foreach ($this->routes as $route) {
            /* @var $route \stubbles\webapp\routing\Route */
            if ($route->matches($calledUri)) {
                return new MatchingRoutes(
                    ['exact' => $route],
                    $route->allowedRequestMethods()
                );
            } elseif ($route->matchesPath($calledUri)) {
                $matching[] = $route;
                $allowedMethods = array_merge(
                    $allowedMethods,
                    $route->allowedRequestMethods()
                );
            }
        }

        return new MatchingRoutes($matching, array_unique($allowedMethods));
    }

    /**
     * allows iteration over all configured routes
     *
     * @return  \Iterator<Route>
     * @since   6.1.0
     */
    public function getIterator(): Traversable
    {
        $routes = $this->routes;
        usort(
            $routes,
            fn(Route $a, Route $b): int => strnatcmp($a->configuredPath(), $b->configuredPath())
        );
        return new ArrayIterator($routes);
    }
}
