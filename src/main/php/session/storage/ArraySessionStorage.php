<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\session\storage;
use stubbles\webapp\session\Session;
/**
 * Session storage that uses an internal array only and is therefore not durable.
 *
 * @since  2.0.0
 */
class ArraySessionStorage implements SessionStorage
{
    /** @var  array<string,mixed> */
    private array $data;

    /**
     * constructor
     */
    public function __construct()
    {
        $this->data = [Session::FINGERPRINT => ''];
    }

    /**
     * removes all data from storage
     */
    public function clear(): SessionStorage
    {
        $this->data = [];
        return $this;
    }

    /**
     * checks whether a value associated with key exists
     */
    public function hasValue(string $key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * returns a value associated with the key or the default value
     */
    public function value(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    /**
     * stores a value associated with the key
     */
    public function putValue(string $key, mixed $value): SessionStorage
    {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * removes a value from the session
     */
    public function removeValue(string $key): SessionStorage
    {
        unset($this->data[$key]);
        return $this;
    }

    /**
     * return an array of all keys registered in this session
     *
     * @return  string[]
     */
    public function valueKeys(): array
    {
        return array_keys($this->data);
    }
}
