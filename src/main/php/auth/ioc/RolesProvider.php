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
use stubbles\webapp\auth\Roles;
/**
 * Can provide the currently logged in user.
 *
 * Be careful with roles injection: it is not possible during app setup, but
 * only after a successful login and authorization. Therefore you should ensure
 * that routes where roles injection is used are configured using
 * withLoginOnly() or withRoleOnly($requiredRight).
 *
 * @since  5.0.0
 * @Singleton
 */
class RolesProvider implements InjectionProvider
{
    /**
     * holds roles instance
     *
     * @type  \stubbles\webapp\auth\Roles
     */
    private static $roles;

    /**
     * stores the roles for further reference
     *
     * @param   \stubbles\webapp\auth\Roles  $roles
     * @return  \stubbles\webapp\auth\Roles
     */
    public static function store(Roles $roles = null)
    {
        self::$roles = $roles;
        return self::$roles;
    }

    /**
     * returns roles of the current user
     *
     * @param   string  $name
     * @return  \stubbles\webapp\auth\Roles
     * @throws  \stubbles\lang\exception\RuntimeException  in case no roles are present
     */
    public function get($name = null)
    {
        if (null !== self::$roles) {
            return self::$roles;
        }

        throw new RuntimeException('No roles available - are you sure login and authorization happened?');
    }
}


