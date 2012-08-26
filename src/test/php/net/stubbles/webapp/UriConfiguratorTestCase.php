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
 * Tests for net\stubbles\webapp\UriConfigurator.
 *
 * @since  1.7.0
 * @group  core
 */
class UriConfiguratorTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  UriConfigurator
     */
    private $uriConfigurator;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->uriConfigurator = new UriConfigurator('example\\DefaultProcessor');
    }

    /**
     * @test
     */
    public function preInterceptAddsPreInterceptorClasses()
    {
        $interceptor = function(WebRequest $request, Response $response)
                       {
                           $response->addHeader('X-Binford', '6100 (More power!)');
                       };
        $this->assertEquals(array('example\\SomePreInterceptor', 'example\\OtherPreInterceptor', $interceptor),
                            $this->uriConfigurator->preIntercept('example\\SomePreInterceptor')
                                                  ->preIntercept('example\\OtherPreInterceptor', '^/foo')
                                                  ->preIntercept($interceptor)
                                                  ->getConfig()
                                                  ->getPreInterceptors(UriRequest::fromString('http://example.net/foo'))
        );
    }

    /**
     * @test
     */
    public function postInterceptAddsPostInterceptorClasses()
    {
        $interceptor = function(WebRequest $request, Response $response)
                       {
                           $response->addHeader('X-Binford', '6100 (More power!)');
                       };
        $this->assertEquals(array('example\\SomePostInterceptor', 'example\\OtherPostInterceptor', $interceptor),
                            $this->uriConfigurator->postIntercept('example\\SomePostInterceptor')
                                                  ->postIntercept('example\\OtherPostInterceptor', '^/foo')
                                                  ->postIntercept($interceptor)
                                                  ->getConfig()
                                                  ->getPostInterceptors(UriRequest::fromString('http://example.net/foo'))
        );
    }

    /**
     * @test
     */
    public function usesDefaultProcessorWhenNoSpecificRequested()
    {
        $this->assertEquals('example\\DefaultProcessor',
                            $this->uriConfigurator->getConfig()
                                                  ->getProcessorForUri(UriRequest::fromString('http://example.net/'))
        );
    }

    /**
     * @test
     */
    public function usesRestProcessorWhenNoSpecificRequested()
    {
        $this->assertEquals('net\\stubbles\\webapp\\rest\\RestProcessor',
                            UriConfigurator::createWithRestProcessorAsDefault()
                                           ->getConfig()
                                           ->getProcessorForUri(UriRequest::fromString('http://example.net/'))
        );
    }

    /**
     * @test
     * @expectedException  net\stubbles\lang\exception\IllegalArgumentException
     */
    public function addProcessorWithNullUriConditionThrowsIllegalArgumentException()
    {
        $this->uriConfigurator->process('example\\ExampleProcessor', null);
    }

    /**
     * @test
     * @expectedException  net\stubbles\lang\exception\IllegalArgumentException
     */
    public function addProcessorWithEmptyUriConditionThrowsIllegalArgumentException()
    {
        $this->uriConfigurator->process('example\\ExampleProcessor', '');
    }

    /**
     * @test
     */
    public function addProcessorClass()
    {
        $this->assertEquals('example\\ExampleProcessor',
                            $this->uriConfigurator->process('example\\ExampleProcessor', '^/new/')
                                                  ->getConfig()
                                                  ->getProcessorForUri(UriRequest::fromString('http://example.net/new/'))
        );
    }

    /**
     * @test
     */
    public function addProcessorClosure()
    {
        $processor = function(UriRequest $calledUri, WebRequest $request, Response $response)
                     {
                         $response->addHeader('X-Binford', '6100 (More power!)');
                     };
        $this->assertEquals($processor,
                            $this->uriConfigurator->process($processor, '^/new/')
                                                  ->getConfig()
                                                  ->getProcessorForUri(UriRequest::fromString('http://example.net/new/'))
        );
    }

    /**
     * @test
     * @expectedException  net\stubbles\lang\exception\IllegalArgumentException
     */
    public function addResourceHandlerWithNullUriConditionThrowsIllegalArgumentException()
    {
        $this->uriConfigurator->addResourceHandler('example\\ExampleResourceHandler', null);
    }

    /**
     * @test
     * @expectedException  net\stubbles\lang\exception\IllegalArgumentException
     */
    public function addResourceHandlerWithEmptyUriConditionThrowsIllegalArgumentException()
    {
        $this->uriConfigurator->addResourceHandler('example\\ExampleResourceHandler', '');
    }

    /**
     * @test
     */
    public function addResourceHandlerEnablesRestProcessor()
    {
        $this->assertEquals('net\\stubbles\\webapp\\rest\\RestProcessor',
                            $this->uriConfigurator->addResourceHandler('example\\ExampleResourceHandler', '^/examples')
                                                  ->getConfig()
                                                  ->getProcessorForUri(UriRequest::fromString('http://example.net/examples/303'))
        );
    }

    /**
     * @test
     */
    public function hasNoResourceHandlersByDefault()
    {
        $this->assertEquals(array(),
                            $this->uriConfigurator->getResourceHandler()
        );
    }

    /**
     * @test
     */
    public function returnsAddedResourceHandlers()
    {
        $this->assertEquals(array('^/examples' => 'example\\ExampleResourceHandler',
                                  '^/users'    => 'example\\UserResourceHandler'
                            ),
                            $this->uriConfigurator->addResourceHandler('example\\ExampleResourceHandler', '^/examples')
                                                  ->addResourceHandler('example\\UserResourceHandler', '^/users', array('text/plain', 'application/xml'))
                                                  ->getResourceHandler()
        );
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function returnsAddedMimeTypes()
    {
        $this->assertEquals(array('^/examples' => array(),
                                  '^/users'    => array('text/plain', 'application/xml')
                            ),
                            $this->uriConfigurator->addResourceHandler('example\\ExampleResourceHandler', '^/examples')
                                                  ->addResourceHandler('example\\UserResourceHandler', '^/users', array('text/plain', 'application/xml'))
                                                  ->getResourceMimeTypes()
        );
    }
}
?>