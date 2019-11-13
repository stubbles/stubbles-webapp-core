<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\session;
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
     * where session data is stored
     *
     * @type  \stubbles\webapp\session\storage\SessionStorage
     */
    private $storage;
    /**
     * if of the session
     *
     * @type  \stubbles\webapp\session\id\SessionId
     */
    private $id;
    /**
     * switch whether session is new or not
     *
     * @type  bool
     */
    private $isNew       = false;

    /**
     * constructor
     *
     * @param  \stubbles\webapp\session\storage\SessionStorage  $storage
     * @param  \stubbles\webapp\session\id\SessionId            $id
     * @param  string                                           $fingerPrint
     */
    public function __construct(SessionStorage $storage, SessionId $id, $fingerPrint)
    {
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
     *
     * @return  bool
     */
    private function isSessionNew(): bool
    {
        return !$this->storage->hasValue(Session::FINGERPRINT);
    }

    /**
     * checks if session was probably hijacked by another user
     *
     * @param   string  $fingerPrint
     * @return  bool
     */
    private function isHijacked($fingerPrint): bool
    {
        return $this->storage->value(Session::FINGERPRINT) !== $fingerPrint;
    }

    /**
     * initializes storage with start time and fingerprint
     *
     * @param  string  $fingerPrint
     */
    private function init(string $fingerPrint)
    {
        $this->storage->putValue(Session::FINGERPRINT, $fingerPrint);
    }

    /**
     * checks whether session has been started
     *
     * Typically, a session is new on the first request of a user,
     * afterwards it should never be new.
     *
     * @return  bool  true if session has been started, else false
     */
    public function isNew(): bool
    {
        return $this->isNew;
    }

    /**
     * returns session id
     *
     * @return  string  the session id
     */
    public function id(): string
    {
        return (string) $this->id;
    }

    /**
     * regenerates the session id but leaves session data
     *
     * @return  \stubbles\webapp\session\Session
     */
    public function regenerateId(): Session
    {
        $this->id->regenerate();
        return $this;
    }

    /**
     * returns the name of the session
     *
     * @return  string
     */
    public function name(): string
    {
        return $this->id->name();
    }

    /**
     * checks if this session is valid
     *
     * @return  bool
     */
    public function isValid(): bool
    {
        return $this->storage->hasValue(Session::FINGERPRINT);
    }

    /**
     * invalidates current session and creates a new one
     *
     * @return  \stubbles\webapp\session\Session
     */
    public function invalidate(): Session
    {
        $this->storage->clear();
        $this->id->invalidate();
        return $this;
    }

    /**
     * checks whether a value associated with key exists
     *
     * @param   string  $key  key where value is stored under
     * @return  bool
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
     * @param   string  $key      key where value is stored under
     * @param   mixed   $default  optional  return this if no data is associated with $key
     * @return  mixed
     * @throws  \LogicException
     */
    public function value(string $key, $default = null)
    {
        if (!$this->isValid()) {
            throw new \LogicException('Session is in an invalid state.');
        }

        if ($this->storage->hasValue($key)) {
            return $this->storage->value($key);
        }

        return $default;
    }

    /**
     * stores a value associated with the key
     *
     * @param   string  $key    key to store value under
     * @param   mixed   $value  data to store
     * @return  \stubbles\webapp\session\Session
     * @throws  \LogicException
     */
    public function putValue(string $key, $value): Session
    {
        if (!$this->isValid()) {
            throw new \LogicException('Session is in an invalid state.');
        }

        $this->storage->putValue($key, $value);
        return $this;
    }

    /**
     * removes a value from the session
     *
     * @param   string  $key  key where value is stored under
     * @return  bool    true if value existed and was removed, else false
     * @throws  \LogicException
     */
    public function removeValue(string $key): bool
    {
        if (!$this->isValid()) {
            throw new \LogicException('Session is in an invalid state.');
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
     * @throws  \LogicException
     */
    public function valueKeys(): array
    {
        if (!$this->isValid()) {
            throw new \LogicException('Session is in an invalid state.');
        }

        return array_values(array_filter(
                $this->storage->valueKeys(),
                function($valueKey)
                {
                    return substr($valueKey, 0, 11) !== '__stubbles_';
                }
        ));
    }
}
