<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\webapp
 */
namespace net\stubbles\webapp\ioc;
use net\stubbles\ioc\Binder;
use net\stubbles\webapp\UriConfigurator;
use net\stubbles\webapp\UriRequest;
/**
 * Tests for net\stubbles\webapp\ioc\WebAppBindingModule.
 *
 * @since  1.7.0
 * @group  ioc
 */
class WebAppBindingModuleTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  WebAppBindingModule
     */
    private $webAppBindingModule;
    /**
     * uri configurator
     *
     * @type  UriConfigurator
     */
    private $uriConfigurator;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->uriConfigurator     = new UriConfigurator('example\\DefaultProcessor');
        $this->webAppBindingModule = new WebAppBindingModule($this->uriConfigurator);
    }

    /**
     * creates injector
     *
     * @return  net\stubbles\ioc\Injector
     */
    private function createInjector()
    {
        $binder = new Binder();
        $this->webAppBindingModule->configure($binder);
        return $binder->getInjector();
    }

    /**
     * @test
     */
    public function bindsGivenUriConfiguration()
    {
        $this->assertTrue($this->createInjector()->hasExplicitBinding('net\\stubbles\\webapp\\UriConfiguration'));
    }

    /**
     * @test
     */
    public function bindsResourceHandlers()
    {
        $this->uriConfigurator->addResourceHandler('example\\ExampleResourceHandler', '^/examples');
        $this->assertTrue($this->createInjector()->hasConstant('net.stubbles.webapp.resource.handler'));
    }

    /**
     * @test
     */
    public function bindsConfiguredResourceHandlers()
    {
        $this->uriConfigurator->addResourceHandler('example\\ExampleResourceHandler', '^/examples');
        $this->assertEquals(array('^/examples' => 'example\\ExampleResourceHandler'),
                            $this->createInjector()->getConstant('net.stubbles.webapp.resource.handler')
        );
    }

    /**
     * @test
     */
    public function doesNotBindAuthConfigIfNotEnabled()
    {
        $this->assertFalse($this->createInjector()->hasExplicitBinding('net\\stubbles\\webapp\\auth\\AuthConfiguration'));
    }

    /**
     * @test
     */
    public function createsAuthConfig()
    {
        $this->assertInstanceOf('net\\stubbles\\webapp\\auth\\AuthConfiguration',
                                $this->webAppBindingModule->enableAuth()
        );
    }

    /**
     * @test
     */
    public function bindAuthConfigIfEnabled()
    {
        $this->webAppBindingModule->enableAuth();
        $this->assertTrue($this->createInjector()->hasExplicitBinding('net\\stubbles\\webapp\\auth\\AuthConfiguration'));
    }
}
?>