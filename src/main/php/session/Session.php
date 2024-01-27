<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\session;
/**
 * Interface for sessions.
 */
interface Session extends \stubbles\ioc\binding\Session
{
    /**
     * key to be associated with the fingerprint of the user
     */
    const FINGERPRINT = '__stubbles_SessionFingerprint';

    /**
     * checks whether session has been started
     *
     * Typically, a session is new on the first request of a user,
     * afterwards it should never be new.
     */
    public function isNew(): bool;

    /**
     * returns session id
     */
    public function id(): string;

    /**
     * regenerates the session id but leaves session data
     */
    public function regenerateId(): self;

    /**
     * returns the name of the session
     */
    public function name(): string;

    /**
     * checks if this session is valid
     */
    public function isValid(): bool;

    /**
     * invalidates current session and creates a new one
     */
    public function invalidate(): self;

    /**
     * removes a value stored under given key from the session
     *
     * Returns true when value existed, false otherwise.
     */
    public function removeValue(string $name): bool;

    /**
     * return an array of all keys registered in this session
     *
     * @return  string[]
     */
    public function valueKeys(): array;
}
