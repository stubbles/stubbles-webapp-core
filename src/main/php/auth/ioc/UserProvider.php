<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\auth\ioc;
use stubbles\ioc\InjectionProvider;
use stubbles\lang\exception\RuntimeException;
use stubbles\webapp\auth\User;
use stubbles\webapp\session\Session;
/**
 * Can provide the currently logged in user.
 *
 * Be careful with user injection: it is not possible during app setup, but only
 * after a session was bound and a successful login. Therefore you should ensure
 * that routes where user injection is used are configured using withLoginOnly()
 * or withRoleOnly($requiredRight).
 *
 * In case you are not sure you can still access the user via the session:
 * if ($session->hasValue(User::SESSION_KEY)) {
 *     $user = $session->getValue(User::SESSION_KEY);
 * }
 *
 * @since  5.0.0
 */
class UserProvider implements InjectionProvider
{
    /**
     * session container
     *
     * @type  \stubbles\webapp\session\Session
     */
    private $session;

    /**
     * constructor
     *
     * @param  \stubbles\webapp\session\Session  $session
     * @Inject
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * returns the value to provide
     *
     * @param   string  $name
     * @return  \stubbles\webapp\auth\User
     * @throws  \stubbles\lang\exception\RuntimeException
     */
    public function get($name = null)
    {
        if ($this->session->hasValue(User::SESSION_KEY)) {
            return $this->session->value(User::SESSION_KEY);
        }

        throw new RuntimeException('No user available - are you sure a login happened?');
    }
}


