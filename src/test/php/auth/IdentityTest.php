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
 * Tests for stubbles\webapp\auth\Identity.
 *
 * @since  6.0.0
 */
class IdentityTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return  \stubbles\webapp\auth\Identity
     */
    private function createIdentity($mockUser = null)
    {
        return new Identity(
                null === $mockUser ? $this->mockUser() : $mockUser,
                new Roles(['admin'])
        );
    }

    /**
     * @return  \PHPUnit_Framework_MockObject_MockObject
     */
    private function mockUser()
    {
        return $this->getMock('stubbles\webapp\auth\User');
    }

    /**
     * @test
     */
    public function isAssociatedWithGivenUser()
    {
        $mockUser = $this->mockUser();
        $this->assertSame(
                $mockUser,
                $this->createIdentity($mockUser)->user()
        );
    }

    /**
     * @test
     */
    public function identityHasRoleWhenGivenRolesContainRole()
    {
        $this->assertTrue($this->createIdentity()->hasRole('admin'));
    }

    /**
     * @test
     */
    public function returnsGivenRoles()
    {
        $this->assertEquals(
                new Roles(['admin']),
                $this->createIdentity()->roles()
        );
    }
}
