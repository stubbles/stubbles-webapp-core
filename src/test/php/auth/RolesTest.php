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
use function bovigo\assert\assert;
use function bovigo\assert\assertEmpty;
use function bovigo\assert\assertFalse;
use function bovigo\assert\assertTrue;
use function bovigo\assert\predicate\equals;
use function bovigo\assert\predicate\isOfSize;
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
        assertEmpty(Roles::none());
    }

    /**
     * @test
     */
    public function hasAmountOfInitialRoles()
    {
        assert(new Roles(['admin']), isOfSize(1));
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

        assert($result, equals($expected));
    }
}
