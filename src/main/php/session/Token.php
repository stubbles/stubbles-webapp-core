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
 * Session tokens can be used to verify that forms have been send by those who
 * requested them before.
 *
 * @since  2.0.0
 * @Singleton
 */
class Token
{
    /**
     * key to be associated with the token for the next request
     */
    const NEXT_TOKEN = '__stubbles_SessionNextToken';
    /**
     * the current token of the session, changes on every instantiation
     */
    private ?string $current = null;

    /**
     * @Inject
     */
    public function __construct(private Session $session) { }

    /**
     * checks if given token equals current token
     */
    public function isValid(string $token): bool
    {
        $this->init();
        return $token === $this->current;
    }

    /**
     * returns next token
     */
    public function next(): string
    {
        $this->init();
        return $this->session->value(self::NEXT_TOKEN);
    }

    private function init(): void
    {
        if (null === $this->current) {
            $this->current = $this->session->value(self::NEXT_TOKEN, md5(uniqid((string) rand())));
            $this->session->putValue(self::NEXT_TOKEN, md5(uniqid((string) rand())));
        }
    }
}
