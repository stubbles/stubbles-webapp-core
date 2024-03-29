<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\routing;

use LogicException;
use stubbles\peer\http\Http;
/**
 * Contains list of routes matching the requested path.
 *
 * @since  5.4.0
 */
class MatchingRoutes
{
    /**
     * @param  Route[]   $routes
     * @param  string[]  $allowedMethods
     */
    public function __construct(
        private array $routes,
        private array $allowedMethods)
    {
        if (in_array(Http::GET, $allowedMethods) && !in_array(Http::HEAD, $allowedMethods)) {
            $this->allowedMethods[] = Http::HEAD;
        }
    }

    public function hasExactMatch(): bool
    {
        return isset($this->routes['exact']);
    }

    /**
     * returns the first route
     *
     * @throws  LogicException
     */
    public function exactMatch(): Route
    {
        if ($this->hasExactMatch()) {
            return $this->routes['exact'];
        }

        throw new LogicException(
            'No exact route available, check with hasExactMatch() before'
        );
    }

    /**
     * checks if any matching route exists
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
