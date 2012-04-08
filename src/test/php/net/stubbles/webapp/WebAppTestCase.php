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
 * Helper class for the test.
 */
class TestWebApp extends WebApp
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
     * @type  WebAppFrontController
     */
    private $webApp;
    /**
     * mocked contains request data
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockWebAppFrontController;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->mockWebAppFrontController = $this->getMockBuilder('net\\stubbles\\webapp\\WebAppFrontController')
                                                ->disableOriginalConstructor()
                                                ->getMock();
        $this->webApp                    = new WebApp($this->mockWebAppFrontController);
    }

    /**
     * @test
     */
    public function annotationPresentOnConstructor()
    {
        $this->assertTrue($this->webApp->getClass()
                                       ->getConstructor()
                                       ->hasAnnotation('Inject')
        );
    }

    /**
     * @test
     */
    public function runCallsFrontController()
    {
        $this->mockWebAppFrontController->expects($this->once())
                                        ->method('process');
        $this->webApp->run();
    }

    /**
     * @test
     */
    public function canCreateWebAppBindingModule()
    {
        $this->assertInstanceOf('net\\stubbles\\webapp\\ioc\\WebAppBindingModule',
                                TestWebApp::callMethod('createWebAppBindingModule',
                                                       $this->getMockBuilder('net\\stubbles\\webapp\\UriConfigurator')
                                                            ->disableOriginalConstructor()
                                                            ->getMock()
                                )
        );
    }

    /**
     * @test
     */
    public function canCreateUriConfigurator()
    {
        $this->assertInstanceOf('net\\stubbles\\webapp\\UriConfigurator',
                                TestWebApp::callMethod('createUriConfigurator',
                                                       'example\\ExampleProcessor'
                                )
        );
    }

    /**
     * @test
     */
    public function canCreateXmlUriConfigurator()
    {
        $this->assertInstanceOf('net\\stubbles\\webapp\\UriConfigurator',
                                TestWebApp::callMethod('createXmlUriConfigurator')
        );
    }

    /**
     * @test
     */
    public function canCreateRestUriConfigurator()
    {
        $this->assertInstanceOf('net\\stubbles\\webapp\\UriConfigurator',
                                TestWebApp::callMethod('createRestUriConfigurator')
        );
    }
}
?>