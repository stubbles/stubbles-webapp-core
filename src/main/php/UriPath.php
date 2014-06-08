<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp;
use stubbles\input\ValueReader;
/**
 * Called path.
 *
 * @since  2.0.0
 */
class UriPath
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
     * creates a pattern for given path
     *
     * @param   string  $path
     * @return  string
     */
    public static function pattern($path)
    {
        return preg_replace('/[{][^}]*[}]/', '([^\/]+)', str_replace('/', '\/', $path));
    }

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
     * creates instance from given pathes
     *
     * @param   string  $configuredPath
     * @param   string  $calledPath
     * @return  UriPath
     */
    public static function from($configuredPath, $calledPath)
    {
        return new self(
                $configuredPath,
                self::parsePathArguments($configuredPath, $calledPath),
                self::extractRemainingPath($configuredPath, $calledPath)
        );
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
     * @return  ValueReader
     * @since   3.3.0
     */
    public function readArgument($name, $default = null)
    {
        if (isset($this->arguments[$name])) {
            return ValueReader::forValue($this->arguments[$name]);
        }

        return ValueReader::forValue($default);
    }

    /**
     * returns remaining path that was not matched by original path
     *
     * @param   string  $default
     * @return  string
     */
    public function remaining($default = null)
    {
        if (null !== $this->remaining) {
            return $this->remaining;
        }

        return $default;
    }

    /**
     * returns remaining path that was not matched by original path
     *
     * @param   string  $default
     * @return  string
     * @deprecated since 4.0.0, use remaining() instead, will be removed with 5.0.0
     */
    public function getRemaining($default = null)
    {
        return $this->remaining($default);
    }

    /**
     * gets path arguments from uri
     *
     * @param   string  $configuredPath
     * @return  string[]
     */
    private static function parsePathArguments($configuredPath, $calledPath)
    {
        $arguments = [];
        preg_match('/^' . self::pattern($configuredPath) . '/', $calledPath, $arguments);
        array_shift($arguments);
        $names  = [];
        $result = [];
        preg_match_all('/[{][^}]*[}]/', str_replace('/', '\/', $configuredPath), $names);
        foreach ($names[0] as $key => $name) {
            if (isset($arguments[$key])) {
                $result[str_replace(['{', '}'], '', $name)] = $arguments[$key];
            }
        }

        return $result;
    }

    /**
     * returns remaining path if there is any
     *
     * @param   string  $configuredPath
     * @return  string
     */
    private static function extractRemainingPath($configuredPath, $calledPath)
    {
        $matches = [];
        preg_match('/(' . self::pattern($configuredPath) . ')([^?]*)?/', $calledPath, $matches);
        $last = count($matches) - 1;
        if (2 > $last) {
            return null;
        }

        if (isset($matches[$last]) && !empty($matches[$last])) {
            return $matches[$last];
        }

        return null;
    }
}
