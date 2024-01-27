<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
     * @param  string  $sessionName  name of session  will be created automatically when not provided
     * @param  string  $id           actual id        will be created automatically when not provided
     * @since  5.0.1
     */
    public function __construct(
        private ?string $sessionName = null,
        private ?string $id = null
    ) { }

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

    public function __toString(): string
    {
        if (null === $this->id) {
            $this->id = $this->create();
        }

        return $this->id;
    }

    private function create(): string
    {
        return md5(uniqid((string) rand(), true));
    }

    public function regenerate(): SessionId
    {
        $this->id = $this->create();
        return $this;
    }

    public function invalidate(): SessionId
    {
        return $this;
    }
}
