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
use stubbles\webapp\auth\User;
/**
 * Can provide the currently logged in user.
 *
 * Be careful with user injection: it is not possible during app setup, but only
 * after a successful login. Therefore you should ensure that routes where user
 * injection is used are configured using withLoginOnly() or
 * withRoleOnly($requiredRight).
 *
 * @since  5.0.0
 * @Singleton
 */
class UserProvider implements InjectionProvider
{
    /**
     * holds user instance
     *
     * @type  \stubbles\webapp\auth\User
     */
    private static $user;

    /**
     * stores the user for further reference
     *
     * @param   \stubbles\webapp\auth\User  $user
     * @return  \stubbles\webapp\auth\User
     */
    public static function store(User $user = null)
    {
        self::$user = $user;
        return self::$user;
    }

    /**
     * returns the current user
     *
     * @param   string  $name
     * @return  \stubbles\webapp\auth\User
     * @throws  \RuntimeException  in case no user is present
     */
    public function get($name = null)
    {
        if (null !== self::$user) {
            return self::$user;
        }

        throw new \RuntimeException('No user available - are you sure a login happened?');
    }
}


