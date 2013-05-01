<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\webapp
 */
namespace net\stubbles\webapp\session;
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
     * session
     *
     * @type  Session
     */
    private $session;
    /**
     * the current token of the session, changes on every instantiation
     *
     * @type  string
     */
    private $current;

    /**
     * constructor
     *
     * @param  Session  $session
     * @Inject
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * checks if given token equals current token
     *
     * @param   string  $token
     * @return  bool
     */
    public function isValid($token)
    {
        $this->init();
        return $token === $this->current;
    }

    /**
     * returns next token
     *
     * @return  string
     */
    public function next()
    {
        $this->init();
        return $this->session->getValue(self::NEXT_TOKEN);
    }

    /**
     * initialize
     */
    private function init()
    {
        if (null === $this->current) {
            $this->current = $this->session->getValue(self::NEXT_TOKEN, md5(uniqid(rand())));
            $this->session->putValue(self::NEXT_TOKEN, md5(uniqid(rand())));
        }
    }
}
?>