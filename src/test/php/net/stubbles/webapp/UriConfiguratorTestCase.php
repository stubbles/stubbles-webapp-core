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
        $this->assertEquals(array('example\\SomePreInterceptor', 'example\\OtherPreInterceptor'),
                            $this->uriConfigurator->preIntercept('example\\SomePreInterceptor')
                                                  ->preIntercept('example\\OtherPreInterceptor', '^/foo')
                                                  ->getConfig()
                                                  ->getPreInterceptors(UriRequest::fromString('http://example.net/foo'))
        );
    }

    /**
     * @test
     */
    public function postInterceptAddsPostInterceptorClasses()
    {
        $this->assertEquals(array('example\\SomePostInterceptor', 'example\\OtherPostInterceptor'),
                            $this->uriConfigurator->postIntercept('example\\SomePostInterceptor')
                                                  ->postIntercept('example\\OtherPostInterceptor', '^/foo')
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
    public function usesXmlProcessorWhenNoSpecificRequested()
    {
        $this->assertEquals('net\\stubbles\\webapp\\xml\\XmlProcessor',
                            UriConfigurator::createWithXmlProcessorAsDefault()
                                           ->getConfig()
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
    public function provideXmlAddsXmlProcessor()
    {
        $this->assertEquals('net\\stubbles\\webapp\\xml\\XmlProcessor',
                            $this->uriConfigurator->provideXml()
                                                  ->getConfig()
                                                  ->getProcessorForUri(UriRequest::fromString('http://example.net/xml/'))
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
        $this->assertEquals(array('example\\ExampleResourceHandler' => '^/examples',
                                  'example\\UserResourceHandler'    => '^/users'
                            ),
                            $this->uriConfigurator->addResourceHandler('^/examples', 'example\\ExampleResourceHandler')
                                                  ->addResourceHandler('^/users', 'example\\UserResourceHandler')
                                                  ->getResourceHandler()
        );
    }

}
?>