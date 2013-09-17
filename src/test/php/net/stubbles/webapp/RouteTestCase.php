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
                         function(WebRequest $request, Response $response, UriPath $uriPath)
                         {
                             $response->setStatusCode(418)
                                      ->write('Hello ' . $uriPath->getArgument('name'));
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
    public function matchesForHeadIfPathOkAndAllowedMethodIsGet()
    {
        $this->assertTrue($this->createRoute()->matches(UriRequest::fromString('http://example.com/hello/world', 'HEAD')));
    }

    /**
     * @test
     */
    public function returnsUriPath()
    {
        $this->assertEquals(new UriPath('/hello/{name}', array('name' => 'world'), null),
                            $this->createRoute()->getUriPath(UriRequest::fromString('http://example.com/hello/world', 'GET'))
        );
    }

    public function returnsGivenCallback()
    {
        $this->assertEquals($this->createRoute()->getCallback());
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
     * @expectedException  net\stubbles\lang\exception\IllegalArgumentException
     */
    public function addInvalidPreInterceptorThrowsIllegalArgumentException()
    {
        $this->createRoute()->preIntercept(303);
    }

    /**
     * @test
     */
    public function hasGivenListOfPreInterceptors()
    {
        $preInterceptor     = function() {};
        $mockPreInterceptor = $this->getMock('net\stubbles\webapp\interceptor\PreInterceptor');
        $mockPreFunction    = 'array_map';
        $this->assertEquals(array(get_class($mockPreInterceptor),
                                  $preInterceptor,
                                  $mockPreInterceptor,
                                  $mockPreFunction
                            ),
                            $this->createRoute()->preIntercept(get_class($mockPreInterceptor))
                                                ->preIntercept($preInterceptor)
                                                ->preIntercept($mockPreInterceptor)
                                                ->preIntercept($mockPreFunction)
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
     * @expectedException  net\stubbles\lang\exception\IllegalArgumentException
     */
    public function addInvalidPostInterceptorThrowsIllegalArgumentException()
    {
        $this->createRoute()->postIntercept(303);
    }

    /**
     * @test
     */
    public function hasGivenListOfPostInterceptors()
    {
        $postInterceptor     = function() {};
        $mockPostInterceptor = $this->getMock('net\stubbles\webapp\interceptor\PostInterceptor');
        $mockPostFunction    = 'array_map';
        $this->assertEquals(array(get_class($mockPostInterceptor),
                                  $postInterceptor,
                                  $mockPostInterceptor,
                                  $mockPostFunction
                            ),
                            $this->createRoute()->postIntercept(get_class($mockPostInterceptor))
                                                ->postIntercept($postInterceptor)
                                                ->postIntercept($mockPostInterceptor)
                                                ->postIntercept($mockPostFunction)
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
    public function doesNotRequireRoleByDefault()
    {
        $this->assertFalse($this->createRoute()->requiresRole());
    }

    /**
     * @test
     */
    public function requiresRoleWhenRoleIsSet()
    {
        $this->assertTrue($this->createRoute()->withRoleOnly('admin')->requiresRole());
    }

    /**
     * @test
     */
    public function requiredRoleIsNullByDefaulz()
    {
        $this->assertNull($this->createRoute()->getRequiredRole());
    }

    /**
     * @test
     */
    public function requiredRoleEqualsGivenRole()
    {
        $this->assertEquals('admin',
                            $this->createRoute()->withRoleOnly('admin')->getRequiredRole()
        );
    }

    /**
     * @test
     */
    public function supportNoMimeTypeByDefault()
    {
        $this->assertEquals(array(),
                            $this->createRoute()->getSupportedMimeTypes()
        );
    }

    /**
     * @test
     */
    public function returnsListOfAddedSupportedMimeTypes()
    {
        $this->assertEquals(array('application/json', 'application/xml'),
                            $this->createRoute()
                                 ->supportsMimeType('application/json')
                                 ->supportsMimeType('application/xml')
                                 ->getSupportedMimeTypes()
        );
    }

    /**
     * @test
     * @since  2.1.1
     */
    public function contentNegotationIsEnabledByDefault()
    {
        $this->assertFalse($this->createRoute()->isContentNegotationDisabled());
    }

    /**
     * @test
     * @since  2.1.1
     */
    public function contentNegotationCanBeDisabled()
    {
        $this->assertTrue($this->createRoute()
                               ->disableContentNegotiation()
                               ->isContentNegotationDisabled()
        );
    }
}
