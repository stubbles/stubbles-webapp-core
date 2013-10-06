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
use net\stubbles\lang;
/**
 * Helper class for the test.
 */
abstract class TestWebApp extends WebApp
{
    /**
     * call method with given name and parameters and return its return value
     *
     * @param   string  $methodName
     * @param   string  $param1      optional
     * @param   string  $param2      optional
     * @return  Object
     */
    public static function callMethod($methodName, $param = null)
    {
        return self::$methodName($param);
    }
}
/**
 * Tests for net\stubbles\webapp\WebApp.
 *
 * @since  1.7.0
 * @group  core
 */
class WebAppTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  TestWebApp
     */
    private $webApp;
    /**
     * mocked request instance
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockRequest;
    /**
     * mocked response instance
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockResponse;
    /**
     * partially mocked routing
     *
     * @type  Routing
     */
    private $routing;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->mockRequest      = $this->getMock('net\stubbles\input\web\WebRequest');
        $this->mockResponse     = $this->getMock('net\stubbles\webapp\response\Response');
        $mockResponseNegotiator = $this->getMockBuilder('net\stubbles\webapp\response\ResponseNegotiator')
                                       ->disableOriginalConstructor()
                                       ->getMock();
        $mockResponseNegotiator->expects($this->any())
                               ->method('negotiateMimeType')
                               ->will($this->returnValue($this->mockResponse));
        $this->routing = $this->getMockBuilder('net\stubbles\webapp\Routing')
                              ->disableOriginalConstructor()
                              ->getMock();

        $this->webApp  = $this->getMock('net\stubbles\webapp\TestWebApp',
                                        array('configureRouting'),
                                        array($this->mockRequest,
                                              $mockResponseNegotiator,
                                              $this->routing
                                        )
                         );
    }

    /**
     * @test
     */
    public function annotationPresentOnConstructor()
    {
        $this->assertTrue(lang\reflectConstructor($this->webApp)->hasAnnotation('Inject'));
    }

    /**
     * @test
     */
    public function annotationPresentOnSetAuthHandlerMethod()
    {
        $method = lang\reflect($this->webApp, 'setAuthHandler');
        $this->assertTrue($method->hasAnnotation('Inject'));
        $this->assertTrue($method->getAnnotation('Inject')->isOptional());
    }

    /**
     * @test
     */
    public function canCreateIoBindingModuleWithSession()
    {
        $this->assertInstanceOf('net\stubbles\webapp\ioc\IoBindingModule',
                                TestWebApp::callMethod('createIoBindingModuleWithSession')
        );
    }

    /**
     * @test
     */
    public function canCreateIoBindingModuleWithoutSession()
    {
        $this->assertInstanceOf('net\stubbles\webapp\ioc\IoBindingModule',
                                TestWebApp::callMethod('createIoBindingModuleWithoutSession')
        );
    }

    /**
     * @test
     */
    public function doesNothingIfResponseNegotiationFails()
    {
        $this->mockRequest->expects($this->once())
                          ->method('isCancelled')
                          ->will($this->returnValue(true));
        $this->mockResponse->expects($this->once())
                           ->method('send');
        $this->webApp->run();
    }

    /**
     * @test
     */
    public function respondsWithError404IfNoRouteFound()
    {
        $this->routing->expects($this->once())
                      ->method('canFindRoute')
                      ->will($this->returnValue(false));
        $this->routing->expects($this->once())
                      ->method('canFindRouteWithAnyMethod')
                      ->will($this->returnValue(false));
        $this->routing->expects($this->never())
                      ->method('getAllowedMethods');
        $this->mockRequest->expects($this->once())
                          ->method('isCancelled')
                          ->will($this->returnValue(false));
        $this->mockResponse->expects($this->once())
                           ->method('notFound');
        $this->mockResponse->expects($this->once())
                           ->method('send');
        $this->webApp->run();
    }

    /**
     * @test
     */
    public function respondsWith20OkIfRequestMethodIsOptionsButRouteHasNoSpecificOptionsSupport()
    {
        $this->routing->expects($this->once())
                      ->method('canFindRoute')
                      ->will($this->returnValue(false));
        $this->routing->expects($this->once())
                      ->method('canFindRouteWithAnyMethod')
                      ->will($this->returnValue(true));
        $this->routing->expects($this->once())
                      ->method('getAllowedMethods')
                      ->will($this->returnValue(array('POST', 'PUT')));
        $this->mockRequest->expects($this->any())
                          ->method('getMethod')
                          ->will($this->returnValue('OPTIONS'));
        $this->mockResponse->expects($this->at(0))
                           ->method('addHeader')
                           ->with($this->equalTo('Allow'), $this->equalTo('POST, PUT, OPTIONS'))
                           ->will($this->returnSelf());
        $this->mockResponse->expects($this->at(1))
                           ->method('addHeader')
                           ->with($this->equalTo('Access-Control-Allow-Methods'), $this->equalTo('POST, PUT, OPTIONS'));
        $this->mockResponse->expects($this->once())
                           ->method('send');
        $this->webApp->run();
    }

    /**
     * @test
     */
    public function respondsWithError405IfPathNotApplicableForGivenRequestMethod()
    {
        $this->routing->expects($this->once())
                      ->method('canFindRoute')
                      ->will($this->returnValue(false));
        $this->routing->expects($this->once())
                      ->method('canFindRouteWithAnyMethod')
                      ->will($this->returnValue(true));
        $this->routing->expects($this->once())
                      ->method('getAllowedMethods')
                      ->will($this->returnValue(array('POST', 'PUT')));
        $this->mockRequest->expects($this->any())
                          ->method('getMethod')
                          ->will($this->returnValue('GET'));
        $this->mockResponse->expects($this->once())
                           ->method('methodNotAllowed')
                           ->with($this->equalTo('GET'), $this->equalTo(array('POST', 'PUT', 'OPTIONS')));
        $this->mockResponse->expects($this->once())
                           ->method('send');
        $this->webApp->run();
    }

    /**
     *
     * @param type $mockRoute
     */
    private function createMockRoute()
    {
        $mockRoute = $this->getMockBuilder('net\stubbles\webapp\ProcessableRoute')
                          ->disableOriginalConstructor()
                          ->getMock();
        $this->routing->expects($this->once())
                      ->method('canFindRoute')
                      ->will($this->returnValue(true));
        $this->routing->expects($this->once())
                      ->method('findRoute')
                      ->will($this->returnValue($mockRoute));
        return $mockRoute;
    }

    /**
     * @test
     */
    public function respondsWithRedirectHttpsUriIfRequiresHttps()
    {
        $mockRoute = $this->createMockRoute();
        $mockRoute->expects($this->once())
                  ->method('switchToHttps')
                  ->will($this->returnValue(true));
        $mockRoute->expects($this->once())
                  ->method('getHttpsUri')
                  ->will($this->returnValue('https://example.net/admin'));
        $this->mockResponse->expects($this->once())
                           ->method('redirect')
                           ->with($this->equalTo('https://example.net/admin'));
        $this->mockResponse->expects($this->once())
                           ->method('send');
        $this->webApp->run();
    }

    /**
     * @test
     */
    public function respondsWithError500IfPathRequiresAuthButNoAuthHandlerSet()
    {
        $mockRoute = $this->createMockRoute();
        $mockRoute->expects($this->once())
                  ->method('switchToHttps')
                  ->will($this->returnValue(false));
        $mockRoute->expects($this->once())
                  ->method('requiresRole')
                  ->will($this->returnValue(true));
        $this->mockResponse->expects($this->once())
                           ->method('internalServerError')
                           ->with($this->equalTo('Requested route requires authorization, but no auth handler defined for application'));
        $this->mockResponse->expects($this->once())
                           ->method('send');
        $this->webApp->run();
    }

    /**
     * @test
     */
    public function respondsWithRedirectToLoginUriIfRequiresAuthAndNoUserLoggedIn()
    {
        $mockRoute = $this->createMockRoute();
        $mockRoute->expects($this->once())
                  ->method('switchToHttps')
                  ->will($this->returnValue(false));
        $mockRoute->expects($this->once())
                  ->method('requiresRole')
                  ->will($this->returnValue(true));
        $mockRoute->expects($this->exactly(2))
                  ->method('getRequiredRole')
                  ->will($this->returnValue('administrator'));
        $this->mockRequest->expects($this->once())
                          ->method('isCancelled')
                          ->will($this->returnValue(false));
        $mockAuthHandler = $this->getMock('net\stubbles\webapp\AuthHandler');
        $mockAuthHandler->expects($this->once())
                        ->method('isAuthorized')
                        ->with($this->equalTo('administrator'))
                        ->will($this->returnValue(false));
        $mockAuthHandler->expects($this->once())
                        ->method('requiresLogin')
                        ->with($this->equalTo('administrator'))
                        ->will($this->returnValue(true));
        $mockAuthHandler->expects($this->once())
                        ->method('getLoginUri')
                        ->will($this->returnValue('https://example.net/login'));
        $this->mockResponse->expects($this->once())
                           ->method('redirect')
                           ->with($this->equalTo('https://example.net/login'));
        $this->mockResponse->expects($this->once())
                           ->method('send');
        $this->webApp->setAuthHandler($mockAuthHandler)->run();
    }

    /**
     * @test
     */
    public function respondsWithError403IfRequiresAuthAndUserHasNoSufficientRights()
    {
        $mockRoute = $this->createMockRoute();
        $mockRoute->expects($this->once())
                  ->method('switchToHttps')
                  ->will($this->returnValue(false));
        $mockRoute->expects($this->once())
                  ->method('requiresRole')
                  ->will($this->returnValue(true));
        $mockRoute->expects($this->exactly(2))
                  ->method('getRequiredRole')
                  ->will($this->returnValue('administrator'));
        $this->mockRequest->expects($this->once())
                          ->method('isCancelled')
                          ->will($this->returnValue(false));
        $mockAuthHandler = $this->getMock('net\stubbles\webapp\AuthHandler');
        $mockAuthHandler->expects($this->any())
                        ->method('isAuthorized')
                        ->with($this->equalTo('administrator'))
                        ->will($this->returnValue(false));
        $mockAuthHandler->expects($this->once())
                        ->method('requiresLogin')
                        ->with($this->equalTo('administrator'))
                        ->will($this->returnValue(false));
        $mockAuthHandler->expects($this->never())
                        ->method('getLoginUri');
        $this->mockResponse->expects($this->once())
                           ->method('forbidden');
        $this->mockResponse->expects($this->once())
                           ->method('send');
        $this->webApp->setAuthHandler($mockAuthHandler)->run();
    }

    /**
     * @test
     */
    public function doesNotExecuteRouteAndPostInterceptorsIfPreInterceptorCancelsRequest()
    {
        $mockRoute = $this->createMockRoute();
        $mockRoute->expects($this->once())
                  ->method('switchToHttps')
                  ->will($this->returnValue(false));
        $mockRoute->expects($this->once())
                  ->method('requiresRole')
                  ->will($this->returnValue(false));
        $mockRoute->expects($this->once())
                  ->method('applyPreInterceptors')
                  ->with($this->equalTo($this->mockRequest),
                         $this->equalTo($this->mockResponse)
                    )
                  ->will($this->returnValue(false));
        $mockRoute->expects($this->never())
                  ->method('process');
        $mockRoute->expects($this->never())
                  ->method('applyPostInterceptors');
        $this->mockResponse->expects($this->once())
                           ->method('send');
        $this->webApp->run();
    }

    /**
     * @test
     */
    public function doesNotExecutePostInterceptorsIfRouteCancelsRequest()
    {
        $mockRoute = $this->createMockRoute();
        $mockRoute->expects($this->once())
                  ->method('switchToHttps')
                  ->will($this->returnValue(false));
        $mockRoute->expects($this->once())
                  ->method('requiresRole')
                  ->will($this->returnValue(false));
        $mockRoute->expects($this->once())
                  ->method('applyPreInterceptors')
                  ->with($this->equalTo($this->mockRequest),
                         $this->equalTo($this->mockResponse)
                    )
                  ->will($this->returnValue(true));
        $mockRoute->expects($this->once())
                  ->method('process')
                  ->with($this->equalTo($this->mockRequest),
                         $this->equalTo($this->mockResponse)
                    )
                  ->will($this->returnValue(false));
        $mockRoute->expects($this->never())
                  ->method('applyPostInterceptors');
        $this->mockResponse->expects($this->once())
                           ->method('send');
        $this->webApp->run();
    }

    /**
     * @test
     */
    public function doesExecuteEverythingIfRequestNotCancelled()
    {
        $mockRoute = $this->createMockRoute();
        $mockRoute->expects($this->once())
                  ->method('switchToHttps')
                  ->will($this->returnValue(false));
        $mockRoute->expects($this->once())
                  ->method('requiresRole')
                  ->will($this->returnValue(false));
        $mockRoute->expects($this->once())
                  ->method('applyPreInterceptors')
                  ->with($this->equalTo($this->mockRequest),
                         $this->equalTo($this->mockResponse)
                    )
                  ->will($this->returnValue(true));
        $mockRoute->expects($this->once())
                  ->method('process')
                  ->with($this->equalTo($this->mockRequest),
                         $this->equalTo($this->mockResponse)
                    )
                  ->will($this->returnValue(true));
        $mockRoute->expects($this->once())
                  ->method('applyPostInterceptors')
                  ->with($this->equalTo($this->mockRequest),
                         $this->equalTo($this->mockResponse)
                    );
        $this->mockResponse->expects($this->once())
                           ->method('send');
        $this->webApp->run();
    }

    /**
     * @test
     */
    public function executesGetWithHeadRequestForSameUri()
    {
        $mockRoute = $this->createMockRoute();
        $mockRoute->expects($this->once())
                  ->method('switchToHttps')
                  ->will($this->returnValue(false));
        $mockRoute->expects($this->once())
                  ->method('requiresRole')
                  ->will($this->returnValue(false));
        $mockRoute->expects($this->once())
                  ->method('applyPreInterceptors')
                  ->with($this->equalTo($this->mockRequest),
                         $this->equalTo($this->mockResponse)
                    )
                  ->will($this->returnValue(true));
        $mockRoute->expects($this->once())
                  ->method('process')
                  ->with($this->equalTo($this->mockRequest),
                         $this->equalTo($this->mockResponse)
                    )
                  ->will($this->returnValue(true));
        $mockRoute->expects($this->once())
                  ->method('applyPostInterceptors')
                  ->with($this->equalTo($this->mockRequest),
                         $this->equalTo($this->mockResponse)
                    );
        $this->mockRequest->expects($this->once())
                          ->method('getMethod')
                          ->will($this->returnValue('HEAD'));
        $this->mockResponse->expects($this->once())
                           ->method('sendHead');
        $this->webApp->run();
    }
}
?>