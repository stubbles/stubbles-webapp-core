<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\webapp
 */
namespace net\stubbles\webapp\auth;
use net\stubbles\webapp\UriRequest;
/**
 * Tests for net\stubbles\webapp\auth\AuthConfiguration.
 *
 * @since  2.0.0
 * @group  auth
 */
class AuthConfigurationTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  AuthConfiguration
     */
    private $authConfiguration;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->authConfiguration = new AuthConfiguration();
    }

    /**
     * @test
     */
    public function requiresNoRoleByDefault()
    {
        $this->assertNull($this->authConfiguration->getRequiredRole(UriRequest::fromString('http://example.net/')));
    }

    /**
     * @test
     */
    public function doesNotRequireSslByDefault()
    {
        $this->assertFalse($this->authConfiguration->requiresSsl(UriRequest::fromString('http://example.net/')));
    }

    /**
     * @test
     */
    public function noRoleRequiredWhenAddedForAnotherUri()
    {
        $this->assertNull($this->authConfiguration->addRole('/restricted', 'user')
                                                  ->getRequiredRole(UriRequest::fromString('http://example.net/'))
        );
    }

    /**
     * @test
     */
    public function roleRequiredWhenAddedForGivenUri()
    {
        $this->assertEquals('user',
                            $this->authConfiguration->addRole('/restricted', 'user')
                                                    ->getRequiredRole(UriRequest::fromString('http://example.net/restricted/foo.html'))
        );
    }

    /**
     * @test
     */
    public function nonSecureNotRoleRequiredWhenAddedForAnotherUri()
    {
        $this->assertNull($this->authConfiguration->addNonSecureRole('/restricted', 'user')
                                                  ->getRequiredRole(UriRequest::fromString('http://example.net/'))
        );
    }

    /**
     * @test
     */
    public function nonSecureRoleRequiredWhenAddedForGivenUri()
    {
        $this->assertEquals('user',
                            $this->authConfiguration->addNonSecureRole('/restricted', 'user')
                                                    ->getRequiredRole(UriRequest::fromString('http://example.net/restricted/foo.html'))
        );
    }

    /**
     * @test
     */
    public function noSslRequiredWhenRoleAddedForAnotherUri()
    {
        $this->assertFalse($this->authConfiguration->addRole('/restricted', 'user')
                                                   ->requiresSsl(UriRequest::fromString('http://example.net/'))
        );
    }

    /**
     * @test
     */
    public function sslRequiredWhenRoleAddedForGivenUri()
    {
        $this->assertTrue($this->authConfiguration->addRole('/restricted', 'user')
                                                  ->requiresSsl(UriRequest::fromString('http://example.net/restricted/foo.html'))
        );
    }

    /**
     * @test
     */
    public function noSslRequiredWhenNonSecureRoleAddedForAnotherUri()
    {
        $this->assertFalse($this->authConfiguration->addNonSecureRole('/restricted', 'user')
                                                   ->requiresSsl(UriRequest::fromString('http://example.net/'))
        );
    }

    /**
     * @test
     */
    public function sslNotRequiredWhenNonSecureRoleAddedForGivenUri()
    {
        $this->assertFalse($this->authConfiguration->addNonSecureRole('/restricted', 'user')
                                                   ->requiresSsl(UriRequest::fromString('http://example.net/restricted/foo.html'))
        );
    }
}
?>