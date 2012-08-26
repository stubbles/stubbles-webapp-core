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
 * Tests for net\stubbles\webapp\ProcessorResolver.
 *
 * @since  2.0.0
 * @group  core
 */
class ProcessorResolverTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  ProcessorResolver
     */
    private $processorResolver;
    /**
     * mocked injector
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockInjector;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->mockInjector      = $this->getMockBuilder('net\stubbles\ioc\Injector')
                                        ->disableOriginalConstructor()
                                        ->getMock();
        $this->processorResolver = new ProcessorResolver($this->mockInjector,
                                                         $this->getMock('net\stubbles\input\web\WebRequest'),
                                                         $this->getMock('net\stubbles\webapp\response\Response')
                                   );
    }

    /**
     * @test
     */
    public function annotationsPresentOnConstructor()
    {
        $this->assertTrue($this->processorResolver->getClass()
                                                  ->getConstructor()
                                                  ->hasAnnotation('Inject')
        );
    }

    /**
     * @test
     */
    public function annotationsPresentOnSetAuthConfigMethod()
    {
        $method = $this->processorResolver->getClass()
                                          ->getMethod('setAuthConfig');
        $this->assertTrue($method->hasAnnotation('Inject'));
        $this->assertTrue($method->getAnnotation('Inject')->isOptional());
    }

    /**
     * @test
     */
    public function doesNotDecorateProcessorWithAuthProcessorIfNoAuthConfigSet()
    {
        $mockProcessor      = $this->getMock('net\stubbles\webapp\Processor');
        $mockProcessorClass = get_class($mockProcessor);
        $this->mockInjector->expects($this->once())
                           ->method('getInstance')
                           ->with($this->equalTo($mockProcessorClass))
                           ->will($this->returnValue($mockProcessor));
        $this->assertInstanceOf($mockProcessorClass,
                                $this->processorResolver->resolve($mockProcessorClass)
        );
    }

    /**
     * @test
     */
    public function doesNotDecorateClosureProcessorWithAuthProcessorIfNoAuthConfigSet()
    {
        $this->mockInjector->expects($this->never())
                           ->method('getInstance');
        $this->assertInstanceOf('net\stubbles\webapp\ClosureProcessor',
                                $this->processorResolver->resolve(function(WebRequest $request, Response $response)
                                                                  {
                                                                      $request->cancel();
                                                                      $response->setStatusCode(418);
                                                                  }
                                )
        );
    }

    /**
     * @test
     */
    public function decoratesProcessorWithAuthProcessorIfAuthConfigSet()
    {
        $mockProcessor = $this->getMock('net\stubbles\webapp\Processor');
        $this->mockInjector->expects($this->exactly(2))
                           ->method('getInstance')
                           ->will($this->onConsecutiveCalls($mockProcessor,
                                                            $this->getMock('net\stubbles\webapp\auth\AuthHandler')
                                  )
                             );
        $this->assertInstanceOf('net\stubbles\webapp\auth\AuthProcessor',
                                $this->processorResolver->setAuthConfig($this->getMock('net\stubbles\webapp\auth\AuthConfiguration'))
                                                        ->resolve(get_class($mockProcessor))
        );
    }

    /**
     * @test
     */
    public function decoratesClosureProcessorWithAuthProcessorIfAuthConfigSet()
    {
        $this->mockInjector->expects($this->once())
                           ->method('getInstance')
                           ->will($this->returnValue($this->getMock('net\stubbles\webapp\auth\AuthHandler')));
        $this->assertInstanceOf('net\stubbles\webapp\auth\AuthProcessor',
                                $this->processorResolver->setAuthConfig($this->getMock('net\stubbles\webapp\auth\AuthConfiguration'))
                                                        ->resolve(function(WebRequest $request, Response $response)
                                                                  {
                                                                      $request->cancel();
                                                                      $response->setStatusCode(418);
                                                                  })
        );
    }
}
?>