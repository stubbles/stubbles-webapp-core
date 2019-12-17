<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\routing;
use stubbles\peer\http\Http;
/**
 * Contains list of routes matching the requested path.
 *
 * @since  5.4.0
 */
class MatchingRoutes
{
    /**
     * list of path matching routes
     *
     * @var  \stubbles\webapp\routing\Route[]
     */
    private $routes;
    /**
     * list of allowed request methods over all matching routes
     *
     * @var  string[]
     */
    private $allowedMethods;

    /**
     * constructor
     *
     * @param  \stubbles\webapp\routing\Route[]  $routes
     * @param  string[]                          $allowedMethods
     */
    public function __construct(array $routes, array $allowedMethods)
    {
        $this->routes         = $routes;
        $this->allowedMethods = $allowedMethods;
        if (in_array(Http::GET, $allowedMethods) && !in_array(Http::HEAD, $allowedMethods)) {
            $this->allowedMethods[] = Http::HEAD;
        }
    }

    /**
     * returns the first route
     *
     * @return  bool
     */
    public function hasExactMatch(): bool
    {
        return isset($this->routes['exact']);
    }

    /**
     * returns the first route
     *
     * @return  \stubbles\webapp\routing\Route
     */
    public function exactMatch(): ?Route
    {
        if ($this->hasExactMatch()) {
            return $this->routes['exact'];
        }

        return null;
    }

    /**
     * checks if any matching route exists
     *
     * @return  bool
     */
    public function exist(): bool
    {
        return count($this->routes) > 0;
    }

    /**
     * returns list of allowed methods for all matching routes
     *
     * @return  string[]
     */
    public function allowedMethods(): array
    {
        return $this->allowedMethods;
    }
}
