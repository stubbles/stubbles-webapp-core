<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\auth;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
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
 */
#[Group('auth')]
class RolesTest extends TestCase
{
    #[Test]
    public function noneYieldsNoRoles(): void
    {
        assertEmpty(Roles::none());
    }

    #[Test]
    public function hasAmountOfInitialRoles(): void
    {
        assertThat(new Roles(['admin']), isOfSize(1));
    }

    #[Test]
    public function doesNotContainNonAddedRole(): void
    {
        $roles = new Roles(['admin']);
        assertFalse($roles->contain('superadmin'));
    }

    #[Test]
    public function containsAddedRole(): void
    {
        $roles = new Roles(['admin']);
        assertTrue($roles->contain('admin'));
    }

    #[Test]
    public function rolesCanBeIterated(): void
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
