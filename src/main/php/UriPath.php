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
     * map of path arguments
     *
     * @var  string[]
     */
    private ?array $arguments = null;

    /**
     * creates a pattern for given path
     */
    public static function pattern(string $path): ?string
    {
        return preg_replace('/[{][^}]*[}]/', '([^\/]+)', \str_replace('/', '\/', $path));
    }

    public function __construct(
        private string $configuredPath,
        private string $calledPath
    ) { }

    /**
     * returns matched path from route configuration
     */
    public function configured(): string
    {
        return $this->configuredPath;
    }

    /**
     * returns actual path that was called
     *
     * @since  4.0.0
     */
    public function actual(): string
    {
        return $this->calledPath;
    }

    /**
     * returns actual path that was called
     */
    public function __toString(): string
    {
        return $this->actual();
    }

    /**
     * checks if path contains argument with given name
     */
    public function hasArgument(string $name): bool
    {
        $this->parsePathArguments();
        return isset($this->arguments[$name]);
    }

    /**
     * returns argument with given name or default if not set
     *
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
        \preg_match('/^' . self::pattern($this->configuredPath) . '/', $this->calledPath, $arguments);
        \array_shift($arguments);
        $names  = [];
        $this->arguments = [];
        \preg_match_all('/[{][^}]*[}]/', \str_replace('/', '\/', $this->configuredPath), $names);
        foreach ($names[0] as $key => $name) {
            /** @var  string  $name */
            if (isset($arguments[$key])) {
                $this->arguments[\str_replace(['{', '}'], '', $name)] = $arguments[$key];
            }
        }
    }

    /**
     * returns remaining path that was not matched by original path
     */
    public function remaining(string $default = ''): string
    {
        $matches = [];
        \preg_match('/(' . self::pattern($this->configuredPath) . ')([^?]*)?/', $this->calledPath, $matches);
        $last = \count($matches) - 1;
        if (2 > $last) {
            return $default;
        }

        if (isset($matches[$last]) && !empty($matches[$last])) {
            return $matches[$last];
        }

        return $default;
    }
}
