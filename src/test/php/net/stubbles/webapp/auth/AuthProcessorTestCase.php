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
 * Tests for net\stubbles\webapp\auth\AuthProcessor.
 *
 * @group  auth
 */
class AuthProcessorTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  AuthProcessor
     */
    private $authProcessor;
    /**
     * mocked processor instance
     *
     * @type  Processor
     */
    private $mockProcessor;
    /**
     * mocked response
     *
     * @type  Response
     */
    private $mockResponse;
    /**
     * mocked auth configuration
     *
     * @type  AuthConfiguration
     */
    private $mockAuthConfig;
    /**
     * mocked auth handler
     *
     * @type  AuthHandler
     */
    private $mockAuthHandler;
    /**
     * uri request to pass around
     *
     * @type  UriRequest
     */
    protected $uriRequest;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->mockProcessor   = $this->getMock('net\stubbles\webapp\Processor');
        $this->mockResponse    = $this->getMock('net\stubbles\webapp\response\Response');
        $this->mockAuthConfig  = $this->getMock('net\stubbles\webapp\auth\AuthConfiguration');
        $this->mockAuthHandler = $this->getMock('net\stubbles\webapp\auth\AuthHandler');
        $this->authProcessor   = new AuthProcessor($this->mockProcessor,
                                                   $this->mockResponse,
                                                   $this->mockAuthConfig,
                                                   $this->mockAuthHandler
                                 );
        $this->uriRequest      = UriRequest::fromString('http://example.net/');
    }

    /**
     * @test
     */
    public function noRequiredRoleCallsDecoratedProcessor()
    {

        $this->mockAuthConfig->expects($this->once())
                             ->method('getRequiredRole')
                             ->with($this->equalTo($this->uriRequest))
                             ->will($this->returnValue(null));
        $this->mockAuthHandler->expects($this->never())
                              ->method('userHasRole');
        $this->mockProcessor->expects($this->once())
                            ->method('startup')
                            ->with($this->equalTo($this->uriRequest));
        $this->mockProcessor->expects($this->once())
                            ->method('process');
        $this->mockProcessor->expects($this->once())
                            ->method('cleanup');
        $this->assertSame($this->authProcessor,
                          $this->authProcessor->startup($this->uriRequest)
                                              ->process()
                                              ->cleanup()
        );
    }

    /**
     * @test
     */
    public function userHasRequriredRoleCallsDecoratedProcessor()
    {
        $this->mockAuthConfig->expects($this->once())
                             ->method('getRequiredRole')
                             ->with($this->equalTo($this->uriRequest))
                            ->will($this->returnValue('guest'));
        $this->mockAuthHandler->expects($this->once())
                              ->method('userHasRole')
                              ->with($this->equalTo('guest'))
                              ->will($this->returnValue(true));
        $this->mockProcessor->expects($this->once())
                            ->method('startup')
                            ->with($this->equalTo($this->uriRequest));
        $this->mockProcessor->expects($this->once())
                            ->method('process');
        $this->mockProcessor->expects($this->once())
                            ->method('cleanup');
        $this->assertSame($this->authProcessor,
                          $this->authProcessor->startup($this->uriRequest)
                                              ->process()
                                              ->cleanup()
        );
    }

    /**
     * @test
     */
    public function roleRequiredButUserDoesNotHaveRoleAndRoleRequiresLoginSetsLocationHeader()
    {
        $this->mockAuthConfig->expects($this->once())
                             ->method('getRequiredRole')
                             ->with($this->equalTo($this->uriRequest))
                            ->will($this->returnValue('admin'));
        $this->mockAuthHandler->expects($this->once())
                              ->method('userHasRole')
                              ->with($this->equalTo('admin'))
                              ->will($this->returnValue(false));
        $this->mockAuthHandler->expects($this->once())
                              ->method('hasUser')
                              ->will($this->returnValue(false));
        $this->mockAuthHandler->expects($this->once())
                              ->method('requiresLogin')
                              ->with($this->equalTo('admin'))
                              ->will($this->returnValue(true));
        $this->mockAuthHandler->expects($this->once())
                              ->method('getLoginUri')
                              ->will($this->returnValue('http://example.net/login'));
        $this->mockProcessor->expects($this->never())
                            ->method('startup');
        $this->mockProcessor->expects($this->never())
                            ->method('process');
        $this->mockProcessor->expects($this->never())
                            ->method('cleanup');
        $this->mockResponse->expects($this->once())
                           ->method('redirect')
                           ->with($this->equalTo('http://example.net/login'));
        $this->assertSame($this->authProcessor,
                          $this->authProcessor->startup($this->uriRequest)
                                              ->process()
                                              ->cleanup()
        );
    }

    /**
     * role required but user does not have role and no user set and role requires no login throws exception
     *
     * If a role is required but there is no user and the role requires no login
     *  - somewhat stupid. Most likely the auth handler is errounous then.
     *
     * @test
     * @expectedException  net\stubbles\lang\exception\RuntimeException
     */
    public function roleRequiredButUserDoesNotHaveRoleAndRoleRequiresNoLoginThrowsRuntimeException()
    {
        $this->mockAuthConfig->expects($this->once())
                             ->method('getRequiredRole')
                             ->with($this->equalTo($this->uriRequest))
                            ->will($this->returnValue('admin'));
        $this->mockAuthHandler->expects($this->once())
                              ->method('userHasRole')
                              ->with($this->equalTo('admin'))
                              ->will($this->returnValue(false));
        $this->mockAuthHandler->expects($this->exactly(2))
                              ->method('hasUser')
                              ->will($this->returnValue(false));
        $this->mockAuthHandler->expects($this->once())
                              ->method('requiresLogin')
                              ->with($this->equalTo('admin'))
                              ->will($this->returnValue(false));
        $this->mockProcessor->expects($this->never())
                            ->method('startup');
        $this->authProcessor->startup($this->uriRequest);
    }

    /**
     * @test
     * @expectedException  net\stubbles\webapp\ProcessorException
     */
    public function roleRequiredButUserDoesNotHaveRoleAndNoLoginRequiredThrowsProcessorException()
    {
        $this->mockAuthConfig->expects($this->once())
                             ->method('getRequiredRole')
                             ->with($this->equalTo($this->uriRequest))
                             ->will($this->returnValue('admin'));
        $this->mockAuthHandler->expects($this->once())
                              ->method('userHasRole')
                              ->with($this->equalTo('admin'))
                              ->will($this->returnValue(false));
        $this->mockAuthHandler->expects($this->exactly(2))
                              ->method('hasUser')
                              ->will($this->returnValue(true));
        $this->mockAuthHandler->expects($this->never())
                              ->method('requiresLogin');
        $this->mockAuthHandler->expects($this->never())
                              ->method('getLoginUrl');
        $this->mockProcessor->expects($this->never())
                            ->method('startup');
        $this->authProcessor->startup($this->uriRequest);
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function doesNotRequireSslWhenNotAuthorized()
    {
        $this->mockAuthConfig->expects($this->once())
                             ->method('getRequiredRole')
                             ->with($this->equalTo($this->uriRequest))
                            ->will($this->returnValue('admin'));
        $this->mockAuthHandler->expects($this->once())
                              ->method('userHasRole')
                              ->with($this->equalTo('admin'))
                              ->will($this->returnValue(false));
        $this->mockAuthHandler->expects($this->once())
                              ->method('hasUser')
                              ->will($this->returnValue(false));
        $this->mockAuthHandler->expects($this->once())
                              ->method('requiresLogin')
                              ->with($this->equalTo('admin'))
                              ->will($this->returnValue(true));
        $this->mockAuthHandler->expects($this->once())
                              ->method('getLoginUri')
                              ->will($this->returnValue('http://example.net/login'));
        $this->assertFalse($this->authProcessor->startup($this->uriRequest)
                                               ->requiresSsl($this->uriRequest)
        );
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function requiresSslWhenAuthorizedAndAuthConfigRequiresSsl()
    {
        $this->mockAuthConfig->expects($this->once())
                             ->method('getRequiredRole')
                             ->with($this->equalTo($this->uriRequest))
                            ->will($this->returnValue('guest'));
        $this->mockAuthHandler->expects($this->once())
                              ->method('userHasRole')
                              ->with($this->equalTo('guest'))
                              ->will($this->returnValue(true));
        $this->mockAuthConfig->expects($this->once())
                             ->method('requiresSsl')
                             ->with($this->equalTo($this->uriRequest))
                             ->will($this->returnValue(true));
        $this->assertTrue($this->authProcessor->startup($this->uriRequest)
                                              ->requiresSsl($this->uriRequest)
        );
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function requiresSslWhenAuthorizedAndDecoratedProcessorSsl()
    {
        $this->mockAuthConfig->expects($this->once())
                             ->method('getRequiredRole')
                             ->with($this->equalTo($this->uriRequest))
                            ->will($this->returnValue('guest'));
        $this->mockAuthHandler->expects($this->once())
                              ->method('userHasRole')
                              ->with($this->equalTo('guest'))
                              ->will($this->returnValue(true));
        $this->mockAuthConfig->expects($this->once())
                             ->method('requiresSsl')
                             ->with($this->equalTo($this->uriRequest))
                             ->will($this->returnValue(false));
        $this->mockProcessor->expects($this->once())
                            ->method('requiresSsl')
                            ->will($this->returnValue(true));
        $this->assertTrue($this->authProcessor->startup($this->uriRequest)
                                              ->requiresSsl($this->uriRequest)
        );
    }
}
?>