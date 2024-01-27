<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\session;

use LogicException;
use stubbles\webapp\session\id\SessionId;
use stubbles\webapp\session\storage\SessionStorage;
/**
 * Base class for session implementations.
 *
 * This class offers a basic implementation for session handling, mainly for
 * the default values of a session which are the start time of the session,
 * the fingerprint of the user and the token of the current and the next
 * request. While an instance is created the class checks the session to prevent
 * the user against session fixation and session hijacking.
 */
class WebSession implements Session
{
    /**
     * switch whether session is new or not
     */
    private bool $isNew = false;

    public function __construct(
        private SessionStorage $storage,
        private SessionId $id,
        string $fingerPrint
    ) {
        $this->storage = $storage;
        $this->id      = $id;
        if ($this->isSessionNew()) {
            $this->isNew = true;
            $this->id->regenerate();
            $this->init($fingerPrint);
        } elseif ($this->isHijacked($fingerPrint)) {
            $this->id->regenerate();
            $this->storage->clear();
            $this->init($fingerPrint);
        }
    }

    /**
     * checks if session is new
     */
    private function isSessionNew(): bool
    {
        return !$this->storage->hasValue(Session::FINGERPRINT);
    }

    /**
     * checks if session was probably hijacked by another user
     */
    private function isHijacked($fingerPrint): bool
    {
        return $this->storage->value(Session::FINGERPRINT) !== $fingerPrint;
    }

    /**
     * initializes storage with start time and fingerprint
     */
    private function init(string $fingerPrint): void
    {
        $this->storage->putValue(Session::FINGERPRINT, $fingerPrint);
    }

    /**
     * checks whether session has been started
     *
     * Typically, a session is new on the first request of a user,
     * afterwards it should never be new.
     */
    public function isNew(): bool
    {
        return $this->isNew;
    }

    /**
     * returns session id
     */
    public function id(): string
    {
        return (string) $this->id;
    }

    /**
     * regenerates the session id but leaves session data
     */
    public function regenerateId(): Session
    {
        $this->id->regenerate();
        return $this;
    }

    /**
     * returns the name of the session
     */
    public function name(): string
    {
        return $this->id->name();
    }

    /**
     * checks if this session is valid
     */
    public function isValid(): bool
    {
        return $this->storage->hasValue(Session::FINGERPRINT);
    }

    /**
     * invalidates current session and creates a new one
     */
    public function invalidate(): Session
    {
        $this->storage->clear();
        $this->id->invalidate();
        return $this;
    }

    /**
     * checks whether a value associated with key exists
     */
    public function hasValue(string $key): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        return $this->storage->hasValue($key);
    }

    /**
     * returns a value associated with the key or the default value
     *
     * @throws  LogicException
     */
    public function value(string $key, mixed $default = null): mixed
    {
        if (!$this->isValid()) {
            throw new LogicException('Session is in an invalid state.');
        }

        if ($this->storage->hasValue($key)) {
            return $this->storage->value($key);
        }

        return $default;
    }

    /**
     * stores a value associated with the key
     *
     * @throws  LogicException
     */
    public function putValue(string $key, $value): void
    {
        if (!$this->isValid()) {
            throw new LogicException('Session is in an invalid state.');
        }

        $this->storage->putValue($key, $value);
    }

    /**
     * removes a value stored under given key from the session
     *
     * Returns true when value existed, false otherwise.
     *
     * @throws  LogicException
     */
    public function removeValue(string $key): bool
    {
        if (!$this->isValid()) {
            throw new LogicException('Session is in an invalid state.');
        }

        if ($this->storage->hasValue($key)) {
            $this->storage->removeValue($key);
            return true;
        }

        return false;
    }

    /**
     * return an array of all keys registered in this session
     *
     * @return  string[]
     * @throws  LogicException
     */
    public function valueKeys(): array
    {
        if (!$this->isValid()) {
            throw new LogicException('Session is in an invalid state.');
        }

        // remove internal values from internal keys
        return array_values(array_filter(
            $this->storage->valueKeys(),
            fn(string $valueKey): bool => substr($valueKey, 0, 11) !== '__stubbles_'
        ));
    }
}
