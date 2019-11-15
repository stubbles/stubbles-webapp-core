<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\auth;
use PHPUnit\Framework\TestCase;

use function bovigo\assert\{
    assertThat,
    assertEmpty,
    assertFalse,
    assertTrue,
    predicate\equals,
    predicate\isOfSize
};
/**
 * Tests for stubbles\webapp\auth\Roles.
 *
 * @since  5.0.0
 * @group  auth
 */
class RolesTest extends TestCase
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
        assertThat(new Roles(['admin']), isOfSize(1));
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
        $result   = [];
        foreach ($roles as $role) {
            $result[] = $role;
        }

        assertThat($result, equals($expected));
    }
}
