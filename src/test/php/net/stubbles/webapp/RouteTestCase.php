<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\webapp
 */
namespace net\stubbles\webapp;
use net\stubbles\input\web\WebRequest;
use net\stubbles\webapp\response\Response;
/**
 * Tests for net\stubbles\webapp\Route.
 *
 * @since  2.0.0
 * @group  core
 */
class RouteTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @expectedException  net\stubbles\lang\exception\IllegalArgumentException
     */
    public function constructRouteWithInvalidCallbackThrowsIllegalArgumentException()
    {
        new Route('/hello', 500, 'GET');
    }

    /**
     * creates instance to test
     *
     * @param   string  $method
     * @return  Route
     */
    private function createRoute($method = 'GET')
    {
        return new Route('/hello/{name}',
                         function(WebRequest $request, Response $response, array $pathArguments)
                         {
                             $response->setStatusCode(418)
                                      ->write('Hello ' . $pathArguments['name']);
                             $request->cancel();
                         },
                         $method
        );
    }

    /**
     * @test
     */
    public function methodIsNullIfNoneGiven()
    {
        $this->assertNull($this->createRoute(null)->getMethod());
    }

    /**
     * @test
     */
    public function returnsGivenMethod()
    {
        $this->assertEquals('GET', $this->createRoute()->getMethod());
    }

    /**
     * @test
     */
    public function doesNotMatchUriRequestIfRequestMethodsDiffer()
    {
        $this->assertFalse($this->createRoute()->matches(UriRequest::fromString('http://example.com/hello/world', 'DELETE')));
    }

    /**
     * @test
     */
    public function doesNotMatchUriRequestIfRequestPathsDiffers()
    {
        $this->assertFalse($this->createRoute()->matches(UriRequest::fromString('http://example.com/other', 'GET')));
    }

    /**
     * @test
     */
    public function matchesIfPathAndMethodAreOk()
    {
        $this->assertTrue($this->createRoute()->matches(UriRequest::fromString('http://example.com/hello/world', 'GET')));
    }

    /**
     * @test
     */
    public function doesNotMatchPathIfDiffers()
    {
        $this->assertFalse($this->createRoute()->matchesPath(UriRequest::fromString('http://example.com/other', 'GET')));
    }

    /**
     * @test
     */
    public function matchesPathIfPathOk()
    {
        $this->assertTrue($this->createRoute()->matchesPath(UriRequest::fromString('http://example.com/hello/world', 'GET')));
    }

    /**
     * @test
     */
    public function processCallsClosureGivenAsCallback()
    {
        $mockRequest = $this->getMock('net\stubbles\input\web\WebRequest');
        $mockRequest->expects($this->once())
                    ->method('cancel');
        $mockResponse = $this->getMock('net\stubbles\webapp\response\Response');
        $mockResponse->expects($this->once())
                     ->method('setStatusCode')
                     ->with($this->equalTo(418))
                     ->will($this->returnSelf());
        $mockResponse->expects($this->once())
                     ->method('write')
                     ->with($this->equalTo('Hello world'));
        $mockInjector = $this->getMockBuilder('net\stubbles\ioc\Injector')
                             ->disableOriginalConstructor()
                             ->getMock();
        $mockInjector->expects($this->never())
                     ->method('getInstance');
        $this->createRoute()->process(UriRequest::fromString('http://example.com/hello/world', 'GET'),
                                      $mockInjector,
                                      $mockRequest,
                                      $mockResponse
        );
    }

    /**
     * helper method for the test
     *
     * @param  WebRequest  $request
     * @param  Response    $response
     */
    public function theCallable(WebRequest $request, Response $response, array $pathArguments)
    {
        $response->setStatusCode(418)
                 ->write('Hello ' . $pathArguments['name']);
        $request->cancel();
    }

    /**
     * @test
     */
    public function processCallsGivenCallback()
    {
        $mockRequest = $this->getMock('net\stubbles\input\web\WebRequest');
        $mockRequest->expects($this->once())
                    ->method('cancel');
        $mockResponse = $this->getMock('net\stubbles\webapp\response\Response');
        $mockResponse->expects($this->once())
                     ->method('setStatusCode')
                     ->with($this->equalTo(418))
                     ->will($this->returnSelf());
        $mockResponse->expects($this->once())
                     ->method('write')
                     ->with($this->equalTo('Hello world'));
        $mockInjector = $this->getMockBuilder('net\stubbles\ioc\Injector')
                             ->disableOriginalConstructor()
                             ->getMock();
        $mockInjector->expects($this->never())
                     ->method('getInstance');
        $route = new Route('/hello/{name}', array($this, 'theCallable'), 'GET');
        $route->process(UriRequest::fromString('http://example.com/hello/world', 'GET'),
                        $mockInjector,
                        $mockRequest,
                        $mockResponse
        );
    }

    /**
     * @test
     * @expectedException  net\stubbles\lang\exception\RuntimeException
     */
    public function processThrowsRuntimeExceptionWhenGivenProcessorClassIsNoProcessor()
    {
        $mockInjector = $this->getMockBuilder('net\stubbles\ioc\Injector')
                             ->disableOriginalConstructor()
                             ->getMock();
        $mockInjector->expects($this->once())
                     ->method('getInstance')
                     ->with($this->equalTo('\stdClass'))
                     ->will($this->returnValue(new \stdClass()));
        $route = new Route('/hello/{name}', '\stdClass', 'GET');
        $route->process(UriRequest::fromString('http://example.com/hello/world', 'GET'),
                        $mockInjector,
                        $this->getMock('net\stubbles\input\web\WebRequest'),
                        $this->getMock('net\stubbles\webapp\response\Response')
        );
    }

    /**
     * @test
     */
    public function processCreatesAndCallsGivenProcessorClass()
    {
        $mockRequest   = $this->getMock('net\stubbles\input\web\WebRequest');
        $mockResponse  = $this->getMock('net\stubbles\webapp\response\Response');
        $mockProcessor = $this->getMock('net\stubbles\webapp\Processor');
        $mockProcessor->expects($this->once())
                      ->method('process')
                      ->with($this->equalTo($mockRequest),
                             $this->equalTo($mockResponse),
                             $this->equalTo(array('name' => 'world'))
                        );
        $mockInjector = $this->getMockBuilder('net\stubbles\ioc\Injector')
                             ->disableOriginalConstructor()
                             ->getMock();
        $mockInjector->expects($this->once())
                     ->method('getInstance')
                     ->with($this->equalTo(get_class($mockProcessor)))
                     ->will($this->returnValue($mockProcessor));
        $route = new Route('/hello/{name}', get_class($mockProcessor), 'GET');
        $route->process(UriRequest::fromString('http://example.com/hello/world', 'GET'),
                        $mockInjector,
                        $mockRequest,
                        $mockResponse
        );
    }

    /**
     * @test
     */
    public function hasNoPreInterceptorsByDefault()
    {
        $this->assertEquals(array(),
                            $this->createRoute()->getPreInterceptors()
        );
    }

    /**
     * @test
     */
    public function hasGivenListOfPreInterceptors()
    {
        $preInterceptor = function() {};
        $this->assertEquals(array('my\PreInterceptor', $preInterceptor),
                            $this->createRoute()->preIntercept('my\PreInterceptor')
                                                ->preIntercept($preInterceptor)
                                                ->getPreInterceptors()
        );
    }

    /**
     * @test
     */
    public function hasNoPostInterceptorsByDefault()
    {
        $this->assertEquals(array(),
                            $this->createRoute()->getPostInterceptors()
        );
    }

    /**
     * @test
     */
    public function hasGivenListOfPostInterceptors()
    {
        $postInterceptor = function() {};
        $this->assertEquals(array('my\PostInterceptor', $postInterceptor),
                            $this->createRoute()->postIntercept('my\PostInterceptor')
                                                ->postIntercept($postInterceptor)
                                                ->getPostInterceptors()
        );
    }

    /**
     * @test
     */
    public function doesNotRequireHttpsByDefault()
    {
        $this->assertFalse($this->createRoute()->requiresHttps());
    }

    /**
     * @test
     */
    public function requiresHttpsWhenWhenRestrictedToHttps()
    {
        $this->assertTrue($this->createRoute()->httpsOnly()->requiresHttps());
    }

    /**
     * @test
     */
    public function doesNotRequireAuthByDefault()
    {
        $this->assertFalse($this->createRoute()->requiresAuth());
    }

    /**
     * @test
     */
    public function requiresAuthWhenRoleIsSet()
    {
        $this->assertTrue($this->createRoute()->withRoleOnly('admin')->requiresAuth());
    }

    /**
     * @test
     */
    public function isAuthorizedWhenNoRoleRequired()
    {
        $mockAuthHandler = $this->getMock('net\stubbles\webapp\AuthHandler');
        $mockAuthHandler->expects($this->never())
                        ->method('userHasRole');
        $this->assertTrue($this->createRoute()->isAuthorized($mockAuthHandler));
    }

    /**
     * @test
     */
    public function isAuthorizedWhenAuthHandlerSignalizesOk()
    {
        $mockAuthHandler = $this->getMock('net\stubbles\webapp\AuthHandler');
        $mockAuthHandler->expects($this->once())
                        ->method('userHasRole')
                        ->with($this->equalTo('admin'))
                        ->will($this->returnValue(true));
        $this->assertTrue($this->createRoute()->withRoleOnly('admin')->isAuthorized($mockAuthHandler));
    }

    /**
     * @test
     */
    public function isNotAuthorizedWhenAuthHandlerDoesNotSignalizeOk()
    {
        $mockAuthHandler = $this->getMock('net\stubbles\webapp\AuthHandler');
        $mockAuthHandler->expects($this->once())
                        ->method('userHasRole')
                        ->with($this->equalTo('admin'))
                        ->will($this->returnValue(false));
        $this->assertFalse($this->createRoute()->withRoleOnly('admin')->isAuthorized($mockAuthHandler));
    }

    /**
     * @test
     */
    public function doesNotRequireLoginWhenNoRoleRequired()
    {
        $this->assertFalse($this->createRoute()->requiresLogin($this->getMock('net\stubbles\webapp\AuthHandler')));
    }

    /**
     * @test
     */
    public function doesNotRequireLoginWhenAuthHandlerSignalizesOk()
    {
        $mockAuthHandler = $this->getMock('net\stubbles\webapp\AuthHandler');
        $mockAuthHandler->expects($this->once())
                        ->method('userHasRole')
                        ->with($this->equalTo('admin'))
                        ->will($this->returnValue(true));
        $this->assertFalse($this->createRoute()->withRoleOnly('admin')->requiresLogin($mockAuthHandler));
    }

    /**
     * @test
     */
    public function doesNotRequireLoginWhenAuthHandlerHasUser()
    {
        $mockAuthHandler = $this->getMock('net\stubbles\webapp\AuthHandler');
        $mockAuthHandler->expects($this->once())
                        ->method('userHasRole')
                        ->with($this->equalTo('admin'))
                        ->will($this->returnValue(false));
        $mockAuthHandler->expects($this->once())
                        ->method('hasUser')
                        ->will($this->returnValue(true));
        $this->assertFalse($this->createRoute()->withRoleOnly('admin')->requiresLogin($mockAuthHandler));
    }

    /**
     * @test
     */
    public function requireLoginWhenAuthHandlerHasNoUser()
    {
        $mockAuthHandler = $this->getMock('net\stubbles\webapp\AuthHandler');
        $mockAuthHandler->expects($this->once())
                        ->method('userHasRole')
                        ->with($this->equalTo('admin'))
                        ->will($this->returnValue(false));
        $mockAuthHandler->expects($this->once())
                        ->method('hasUser')
                        ->will($this->returnValue(false));
        $this->assertTrue($this->createRoute()->withRoleOnly('admin')->requiresLogin($mockAuthHandler));
    }
}
?>