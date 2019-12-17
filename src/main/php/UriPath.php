<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
     * path configured in routing
     *
     * @var  string
     */
    private $configuredPath;
    /**
     * complete called path from request
     *
     * @var  string
     */
    private $calledPath;
    /**
     * map of path arguments
     *
     * @var  string[]
     */
    private $arguments;

    /**
     * creates a pattern for given path
     *
     * @param   string  $path
     * @return  string
     */
    public static function pattern(string $path): ?string
    {
        return preg_replace('/[{][^}]*[}]/', '([^\/]+)', str_replace('/', '\/', $path));
    }

    /**
     * constructor
     *
     * @param  string  $configuredPath  path configured in routing
     * @param  string  $calledPath      complete called path from request
     */
    public function __construct(string $configuredPath, string $calledPath)
    {
        $this->configuredPath = $configuredPath;
        $this->calledPath     = $calledPath;
    }

    /**
     * returns matched path from route configuration
     *
     * @return  string
     */
    public function configured(): string
    {
        return $this->configuredPath;
    }

    /**
     * returns actual path that was called
     *
     * @return  string
     * @since   4.0.0
     */
    public function actual(): string
    {
        return $this->calledPath;
    }

    /**
     * returns actual path that was called
     *
     * @return  string
     */
    public function __toString(): string
    {
        return $this->actual();
    }

    /**
     * checks if path contains argument with given name
     *
     * @param   string  $name
     * @return  bool
     */
    public function hasArgument(string $name): bool
    {
        $this->parsePathArguments();
        return isset($this->arguments[$name]);
    }

    /**
     * returns argument with given name or default if not set
     *
     * @param   string  $name
     * @return  \stubbles\input\ValueReader
     * @since   3.3.0
     */
    public function readArgument(string $name): ValueReader
    {
        $this->parsePathArguments();
        if (isset($this->arguments[$name])) {
            return ValueReader::forValue($this->arguments[$name]);
        }

        return ValueReader::forValue(null);
    }

    /**
     * parses path arguments from called path
     */
    private function parsePathArguments(): void
    {
        if (null !== $this->arguments) {
            return;
        }

        $arguments = [];
        preg_match('/^' . self::pattern($this->configuredPath) . '/', $this->calledPath, $arguments);
        array_shift($arguments);
        $names  = [];
        $this->arguments = [];
        preg_match_all('/[{][^}]*[}]/', str_replace('/', '\/', $this->configuredPath), $names);
        foreach ($names[0] as $key => $name) {
            if (isset($arguments[$key])) {
                $this->arguments[str_replace(['{', '}'], '', $name)] = $arguments[$key];
            }
        }
    }

    /**
     * returns remaining path that was not matched by original path
     *
     * @param   string  $default
     * @return  string
     */
    public function remaining(string $default = ''): string
    {
        $matches = [];
        preg_match('/(' . self::pattern($this->configuredPath) . ')([^?]*)?/', $this->calledPath, $matches);
        $last = count($matches) - 1;
        if (2 > $last) {
            return $default;
        }

        if (isset($matches[$last]) && !empty($matches[$last])) {
            return $matches[$last];
        }

        return $default;
    }
}
