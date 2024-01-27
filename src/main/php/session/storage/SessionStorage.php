<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\session\storage;
/**
 * Interface for session storages.
 *
 * @since  2.0.0
 */
interface SessionStorage
{
    /**
     * removes all data from storage
     */
    public function clear(): self;

    /**
     * checks whether a value associated with key exists
     */
    public function hasValue(string $key): bool;

    /**
     * returns a value associated with the key or the default value
     */
    public function value(string $key): mixed;

    /**
     * stores a value associated with the key
     */
    public function putValue(string $key, mixed $value): self;

    /**
     * removes a value from the storage
     */
    public function removeValue(string $key): self;

    /**
     * return an array of all keys registered in this storage
     *
     * @return  string[]
     */
    public function valueKeys(): array;
}
