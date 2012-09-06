<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\webapp
 */
namespace net\stubbles\webapp;
use net\stubbles\lang\BaseObject;
/**
 * Called path.
 *
 * @since  2.0.0
 */
class UriPath extends BaseObject
{
    /**
     * matched path from route configuration
     *
     * @type  string
     */
    private $matched;
    /**
     * map of path arguments
     *
     * @type  string[]
     */
    private $arguments;
    /**
     * remaining path that was not matched by original path
     *
     * @type  string
     */
    private $remaining;

    /**
     * constructor
     *
     * @param  string    $matched
     * @param  string[]  $arguments
     * @param  string    $remaining
     */
    public function __construct($matched, array $arguments, $remaining)
    {
        $this->matched   = $matched;
        $this->arguments = $arguments;
        $this->remaining = $remaining;
    }

    /**
     * returns matched path from route configuration
     *
     * @return  string
     */
    public function getMatched()
    {
        return $this->matched;
    }

    /**
     * checks if path contains argument with given name
     *
     * @param   string  $name
     * @return  bool
     */
    public function hasArgument($name)
    {
        return isset($this->arguments[$name]);
    }

    /**
     * returns argument with given name or default if not set
     *
     * @param   string  $name
     * @param   bool    $default
     * @return  string
     */
    public function getArgument($name, $default = null)
    {
        if (isset($this->arguments[$name])) {
            return $this->arguments[$name];
        }

        return $default;
    }

    /**
     * returns remaining path that was not matched by original path
     *
     * @param   string  $default
     * @return  string
     */
    public function getRemaining($default = null)
    {
        if (null !== $this->remaining) {
            return $this->remaining;
        }

        return $default;
    }
}
?>