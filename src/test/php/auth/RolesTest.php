<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\auth;
/**
 * Tests for stubbles\webapp\auth\Roles.
 *
 * @since  5.0.0
 * @group  auth
 */
class RolesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function noneYieldsNoRoles()
    {
        assertEquals(0, count(Roles::none()));
    }

    /**
     * @test
     */
    public function hasAmountOfInitialRoles()
    {
        assertEquals(1, count(new Roles(['admin'])));
    }

    /**
     * @test
     */
    public function doesNotContainNonAddedRole()
    {
        $roles = new Roles(['admin']);
        assertFalse($roles->contain('superadmin'));
    }

    /**
     * @test
     */
    public function containsAddedRole()
    {
        $roles = new Roles(['admin']);
        assertTrue($roles->contain('admin'));
    }

    /**
     * @test
     */
    public function rolesCanBeIterated()
    {
        $expected = ['admin', 'superadmin'];
        $roles    = new Roles($expected);
        foreach ($roles as $role) {
            $result[] = $role;
        }

        assertEquals($expected, $result);
    }
}
