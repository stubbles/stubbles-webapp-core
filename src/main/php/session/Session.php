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
     *
     * @return  bool  true if session has been started, else false
     */
    public function isNew(): bool;

    /**
     * returns session id
     *
     * @return  string  the session id
     */
    public function id(): string;

    /**
     * regenerates the session id but leaves session data
     *
     * @return  Session
     */
    public function regenerateId(): self;

    /**
     * returns the name of the session
     *
     * @return  string
     */
    public function name(): string;

    /**
     * checks if this session is valid
     *
     * @return  bool
     */
    public function isValid(): bool;

    /**
     * invalidates current session and creates a new one
     *
     * @return  Session
     */
    public function invalidate(): self;

    /**
     * removes a value from the session
     *
     * @param   string  $name  key where value is stored under
     * @return  bool    true if value existed and was removed, else false
     */
    public function removeValue(string $name): bool;

    /**
     * return an array of all keys registered in this session
     *
     * @return  string[]
     */
    public function valueKeys(): array;
}
