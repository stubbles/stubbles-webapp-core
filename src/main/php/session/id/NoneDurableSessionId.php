<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\session\id;
/**
 * Session id which is always created new.
 *
 * @since  2.0.0
 */
class NoneDurableSessionId implements SessionId
{
    /**
     * actual id
     *
     * @type  string
     */
    private $id;
    /**
     * name of session
     *
     * @type  string
     */
    private $sessionName;

    /**
     * constructor
     *
     * @param  string  $sessionName  name of session  optional  will be created automatically when not provided
     * @param  string  $id           actual id        optional  will be created automatically when not provided
     * @since  5.0.1
     */
    public function __construct(string $sessionName = null, string $id = null)
    {
        $this->sessionName = $sessionName;
        $this->id          = $id;
    }

    /**
     * returns session name
     *
     * @return  string
     */
    public function name(): string
    {
        if (null === $this->sessionName) {
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $this->sessionName = '';
            for ($i = 0; $i < 32; $i++) {
                $this->sessionName .= $characters[rand(0, strlen($characters) - 1)];
            }
        }

        return $this->sessionName;
    }

    /**
     * reads session id
     *
     * @return  string
     */
    public function __toString(): string
    {
        if (null === $this->id) {
            $this->id = $this->create();
        }

        return $this->id;
    }

    /**
     * creates session id
     *
     * @return  string
     */
    private function create(): string
    {
        return md5(uniqid((string) rand(), true));
    }

    /**
     * stores session id for given session name
     *
     * @return  \stubbles\webapp\session\id\SessionId
     */
    public function regenerate(): SessionId
    {
        $this->id = $this->create();
        return $this;
    }

    /**
     * invalidates session id
     *
     * @return  \stubbles\webapp\session\id\SessionId
     */
    public function invalidate(): SessionId
    {
        return $this;
    }
}
