<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\auth;
use bovigo\callmap\NewInstance;
use PHPUnit\Framework\TestCase;

use function bovigo\assert\assertThat;
use function bovigo\assert\assertTrue;
use function bovigo\assert\predicate\equals;
use function bovigo\assert\predicate\isSameAs;
/**
 * Tests for stubbles\webapp\auth\Identity.
 *
 * @since  6.0.0
 * @group  auth
 */
class IdentityTest extends TestCase
{
    private function createIdentity(User $user = null): Identity
    {
        return new Identity(
                null === $user ? NewInstance::of(User::class) : $user,
                new Roles(['admin'])
        );
    }

    /**
     * @test
     */
    public function isAssociatedWithGivenUser()
    {
        $user = NewInstance::of(User::class);
        assertThat(
                $this->createIdentity($user)->user(),
                isSameAs($user)
        );
    }

    /**
     * @test
     */
    public function identityHasRoleWhenGivenRolesContainRole()
    {
        assertTrue($this->createIdentity()->hasRole('admin'));
    }

    /**
     * @test
     */
    public function returnsGivenRoles()
    {
        assertThat($this->createIdentity()->roles(), equals(new Roles(['admin'])));
    }
}
