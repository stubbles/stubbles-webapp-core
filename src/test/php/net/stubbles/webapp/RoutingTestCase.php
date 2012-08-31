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
use net\stubbles\peer\http\AcceptHeader;
/**
 * Tests for net\stubbles\webapp\Routing.
 *
 * @since  2.0.0
 * @group  core
 */
class RoutingTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  Routing
     */
    private $routing;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->routing = new Routing(UriRequest::fromString('http://example.net/hello', 'GET'));
    }

    /**
     * @test
     */
    public function returnsNullOnRouteSelectionWhenNoRouteAdded()
    {
        $this->assertNull($this->routing->findRoute());
    }

    /**
     * @test
     */
    public function canNotFindAnyRouteForPathWhenNoRouteAdded()
    {
        $this->assertFalse($this->routing->canFindRouteWithAnyMethod());
    }

    /**
     * @test
     */
    public function canNotFindRouteForPathWhenNoRouteAdded()
    {
        $this->assertFalse($this->routing->canFindRoute());
    }

    /**
     * @test
     */
    public function hasNoAllowedMethodsWhenNoRouteAdded()
    {
        $this->assertEquals(array(), $this->routing->getAllowedMethods());
    }

    /**
     * @test
     */
    public function returnsNullOnRouteSelectionWhenNoSuitableRouteAdded()
    {
        $this->routing->onHead('/hello', function() {});
        $this->routing->onGet('/foo', function() {});
        $this->assertNull($this->routing->findRoute());
    }

    /**
     * @test
     */
    public function canFindAnyRouteWhenRouteForOtherMethodAdded()
    {
        $this->routing->onHead('/hello', function() {});
        $this->routing->onPost('/hello', function() {});
        $this->routing->onGet('/foo', function() {});
        $this->assertTrue($this->routing->canFindRouteWithAnyMethod());
    }

    /**
     * @test
     */
    public function canNotFindRouteWhenRouteForOtherMethodAddedOnly()
    {
        $this->routing->onHead('/hello', function() {});
        $this->routing->onPost('/hello', function() {});
        $this->routing->onGet('/foo', function() {});
        $this->assertFalse($this->routing->canFindRoute());
    }

    /**
     * @test
     */
    public function hasListOfAllowedMethodsWhenRouteForOtherMethodAdded()
    {
        $this->routing->onHead('/hello', function() {});
        $this->routing->onPost('/hello', function() {});
        $this->routing->onGet('/foo', function() {});
        $this->routing->onPut('/foo', function() {});
        $this->routing->onDelete('/foo', function() {});
        $this->assertEquals(array('HEAD', 'POST'), $this->routing->getAllowedMethods());
    }

    /**
     * @test
     */
    public function returnsRouteWhichFitsMethodAndPath()
    {
        $route = $this->routing->onGet('/hello', function() {});
        $this->routing->onGet('/foo', function() {});
        $this->assertSame($route, $this->routing->findRoute());
    }

    /**
     * @test
     */
    public function hasNoGlobalPreInterceptorsByDefault()
    {
        $this->assertEquals(array(), $this->routing->getPreInterceptors());
    }

    /**
     * @test
     */
    public function hasNoGlobalPreInterceptorsForDifferentMethod()
    {
        $this->assertEquals(array(),
                            $this->routing->preInterceptOnHead('some\PreInterceptor')
                                          ->preInterceptOnPost('some\PreInterceptor')
                                          ->preInterceptOnPut('some\PreInterceptor')
                                          ->preInterceptOnDelete('some\PreInterceptor')
                                          ->getPreInterceptors());
    }

    /**
     * @test
     */
    public function hasGlobalPreInterceptorsEvenWhenNoRouteSelected()
    {
        $preInterceptor = function() {};
        $this->assertEquals(array('some\PreInterceptor', $preInterceptor),
                            $this->routing->preIntercept('some\PreInterceptor')
                                          ->preInterceptOnGet($preInterceptor)
                                          ->getPreInterceptors());
    }

    /**
     * @test
     */
    public function hasGlobalPreInterceptorsEvenWhenRouteSelected()
    {
        $this->routing->onGet('/hello', function() {});
        $preInterceptor = function() {};
        $this->assertEquals(array('some\PreInterceptor', $preInterceptor),
                            $this->routing->preIntercept('some\PreInterceptor')
                                          ->preInterceptOnGet($preInterceptor)
                                          ->getPreInterceptors());
    }

    /**
     * @test
     */
    public function mergesGlobalAndRoutePreInterceptors()
    {
        $this->routing->onGet('/hello', function() {})
                      ->preIntercept('other\PreInterceptor');
        $preInterceptor = function() {};
        $this->assertEquals(array('some\PreInterceptor', $preInterceptor, 'other\PreInterceptor'),
                            $this->routing->preIntercept('some\PreInterceptor')
                                          ->preInterceptOnGet($preInterceptor)
                                          ->getPreInterceptors());
    }

    /**
     * @test
     */
    public function hasNoGlobalPostInterceptorsByDefault()
    {
        $this->assertEquals(array(), $this->routing->getPostInterceptors());
    }

    /**
     * @test
     */
    public function hasNoGlobalPostInterceptorsForDifferentMethod()
    {
        $this->assertEquals(array(),
                            $this->routing->postInterceptOnHead('some\PostInterceptor')
                                          ->postInterceptOnPost('some\PostInterceptor')
                                          ->postInterceptOnPut('some\PostInterceptor')
                                          ->postInterceptOnDelete('some\PostInterceptor')
                                          ->getPostInterceptors());
    }

    /**
     * @test
     */
    public function hasGlobalPostInterceptorsEvenWhenNoRouteSelected()
    {
        $postInterceptor = function() {};
        $this->assertEquals(array('some\PostInterceptor', $postInterceptor),
                            $this->routing->postIntercept('some\PostInterceptor')
                                          ->postInterceptOnGet($postInterceptor)
                                          ->getPostInterceptors());
    }

    /**
     * @test
     */
    public function hasGlobalPostInterceptorsEvenWhenRouteSelected()
    {
        $this->routing->onGet('/hello', function() {});
        $postInterceptor = function() {};
        $this->assertEquals(array('some\PostInterceptor', $postInterceptor),
                            $this->routing->postIntercept('some\PostInterceptor')
                                          ->postInterceptOnGet($postInterceptor)
                                          ->getPostInterceptors());
    }

    /**
     * @test
     */
    public function mergesGlobalAndRoutePostInterceptors()
    {
        $this->routing->onGet('/hello', function() {})
                      ->postIntercept('other\PostInterceptor');
        $postInterceptor = function() {};
        $this->assertEquals(array('some\PostInterceptor', $postInterceptor, 'other\PostInterceptor'),
                            $this->routing->postIntercept('some\PostInterceptor')
                                          ->postInterceptOnGet($postInterceptor)
                                          ->getPostInterceptors());
    }

    /**
     * @test
     */
    public function supportsNoMimeTypeByDefault()
    {
        $this->assertEquals(array(),
                            $this->routing->getSupportedMimeTypes()
        );
    }

    /**
     * @test
     */
    public function supportsGlobalAndRouteMimeTypesWhenRouteFound()
    {
        $this->routing->onGet('/hello', function() {})
                      ->supportsMimeType('application/json');
        $this->assertEquals(array('text/html',
                                  'application/json',
                                  'application/xml'
                            ),
                            $this->routing->supportsMimeType('application/xml')
                                          ->getSupportedMimeTypes()
        );
    }

    /**
     * @test
     */
    public function returnsFirstMimeTypeWhenAcceptHeaderIsEmpty()
    {

        $this->assertEquals('application/json',
                            $this->routing->supportsMimeType('application/json')
                                          ->supportsMimeType('application/xml')
                                          ->negotiateMimeType(new AcceptHeader())
        );
    }

    /**
     * @test
     */
    public function returnsFirstMimeTypeFromRouteWhenAcceptHeaderIsEmpty()
    {

        $this->routing->onGet('/hello', function() {});
        $this->assertEquals('text/html',
                            $this->routing->supportsMimeType('application/json')
                                          ->supportsMimeType('application/xml')
                                          ->negotiateMimeType(new AcceptHeader())
        );
    }

    /**
     * @test
     */
    public function returnsMimeTypeWithGreatesPriorityAccordingToAcceptHeader()
    {
        $this->routing->onGet('/hello', function() {});
        $this->assertEquals('text/html',
                            $this->routing->supportsMimeType('application/json')
                                          ->supportsMimeType('application/xml')
                                          ->negotiateMimeType(AcceptHeader::parse('text/*;q=0.3, text/html;q=0.7, application/json;q=0.4, */*;q=0.5'))
        );
    }

    /**
     * @test
     */
    public function returnsBestMatchMimeTypeAccordingToAcceptHeader()
    {
        $this->routing->onGet('/hello', function() {})
                      ->disableDefaultHtmlMimeType();
        $this->assertEquals('application/json',
                            $this->routing->supportsMimeType('application/json')
                                          ->supportsMimeType('application/xml')
                                          ->negotiateMimeType(AcceptHeader::parse('text/*;q=0.3, text/html;q=0.7, application/json;q=0.4, */*;q=0.5'))
        );
    }

    /**
     * @test
     */
    public function returnsNoMimeTypeWhenNoMatchWithAcceptHeaderFound()
    {
        $this->assertNull($this->routing->supportsMimeType('application/json')
                                        ->supportsMimeType('application/xml')
                                        ->negotiateMimeType(AcceptHeader::parse('text/*;q=0.3, text/html;q=0.7'))
        );
    }
}
?>