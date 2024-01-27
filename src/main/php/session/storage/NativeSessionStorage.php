<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\session\storage;
use stubbles\webapp\session\id\SessionId;
/**
 * Session storage using default PHP sessions.
 *
 * This session storage offers session handling based on the default PHP session
 * functions.
 *
 * @since  2.0.0
 */
class NativeSessionStorage implements SessionStorage, SessionId
{
    /**
     * switch whether storage is already initialized or not
     */
    private bool $initialized = false;

    public function __construct(private string $sessionName)
    {
        session_name($this->sessionName);
    }

    private function init(): void
    {
        if ($this->initialized || session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_start();
        $this->initialized = true;
    }

    public function name(): string
    {
        return $this->sessionName;
    }

    public function __toString(): string
    {
        $this->init();
        return session_id();
    }

    public function regenerate(): SessionId
    {
        $this->init();
        @session_regenerate_id(true);
        return $this;
    }

    public function invalidate(): SessionId
    {
        if ($this->initialized && session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        return $this->regenerate();
    }

    /**
     * removes all data from storage
     */
    public function clear(): SessionStorage
    {
        $_SESSION = [];
        return $this;
    }

    /**
     * checks whether a value associated with key exists
     */
    public function hasValue(string $key): bool
    {
        $this->init();
        return isset($_SESSION[$key]);
    }

    /**
     * returns a value associated with the key or the default value
     */
    public function value(string $key): mixed
    {
        $this->init();
        return $_SESSION[$key] ?? null;
    }

    /**
     * stores a value associated with the key
     */
    public function putValue(string $key, mixed $value): SessionStorage
    {
        $this->init();
        $_SESSION[$key] = $value;
        return $this;
    }

    /**
     * removes a value from the session
     */
    public function removeValue(string $key): SessionStorage
    {
        $this->init();
        unset($_SESSION[$key]);
        return $this;
    }

    /**
     * return an array of all keys registered in this session
     *
     * @return  string[]
     */
    public function valueKeys(): array
    {
        $this->init();
        return array_keys($_SESSION);
    }
}
