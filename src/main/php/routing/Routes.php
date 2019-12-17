<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\routing;
/**
 * Contains list of available routes.
 *
 * @since  5.4.0
 */
class Routes implements \IteratorAggregate
{
    /**
     * list of routes for the web app
     *
     * @var  \stubbles\webapp\routing\Route[]
     */
    private $routes = [];

    /**
     * add a route definition
     *
     * @param   \stubbles\webapp\routing\Route  $route
     * @return  \stubbles\webapp\routing\Route
     */
    public function add(Route $route): Route
    {
        $this->routes[] = $route;
        return $route;
    }

    /**
     * finds route based on called uri
     *
     * @param   \stubbles\webapp\routing\CalledUri  $calledUri
     * @return  \stubbles\webapp\routing\MatchingRoutes
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
     * @return  \Traversable
     * @since   6.1.0
     */
    public function getIterator(): \Traversable
    {
        $routes = $this->routes;
        usort(
                $routes,
                function(Route $a, Route $b)
                {
                    return strnatcmp($a->configuredPath(), $b->configuredPath());
                }
        );
        return new \ArrayIterator($routes);
    }
}
