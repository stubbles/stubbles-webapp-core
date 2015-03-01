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
use stubbles\lang\reflect;
use stubbles\webapp\auth\Roles;
/**
 * Test for stubbles\webapp\auth\ioc\RolesProvider.
 *
 * @since  5.0.0
 * @group  auth
 * @group  ioc
 */
class RolesProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  \stubbles\webapp\auth\ioc\RolesProvider
     */
    private $rolesProvider;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->rolesProvider = new RolesProvider();
    }

    /**
     * clean up test environment
     */
    public function tearDown()
    {
        RolesProvider::store(null);
    }

    /**
     * @test
     */
    public function annotationsPresentOnClass()
    {
        $this->assertTrue(
                reflect\annotationsOf($this->rolesProvider)
                        ->contain('Singleton')
        );
    }

    /**
     * @test
     */
    public function isProviderForRoles()
    {
        $this->assertEquals(
                get_class($this->rolesProvider),
                reflect\annotationsOf('stubbles\webapp\auth\Roles')
                    ->firstNamed('ProvidedBy')
                    ->getValue()
                    ->getName()
        );
    }

    /**
     * @test
     * @expectedException  RuntimeException
     */
    public function throwsRuntimeExceptionWhenNoUserInSession()
    {
        $this->rolesProvider->get();
    }

    /**
     * @test
     */
    public function returnsRolesPreviouslyStored()
    {
        $roles = new Roles(['admin']);
        $this->assertSame($roles, RolesProvider::store($roles));
        $this->assertSame($roles, $this->rolesProvider->get());
    }
}
