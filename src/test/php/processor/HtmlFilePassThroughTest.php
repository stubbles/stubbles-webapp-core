<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\processor;
use org\bovigo\vfs\vfsStream;
use stubbles\lang\reflect;
use stubbles\webapp\UriPath;
/**
 * Test for stubbles\webapp\processor\HtmlFilePassThrough.
 *
 * @group  processor
 * @since  4.0.0
 */
class HtmlFilePassThroughTest extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  HtmlFilePassThrough
     */
    private $htmlFilePassThrough;
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
     * set up the test environment
     */
    public function setUp()
    {
        $root = vfsStream::setup();
        vfsStream::newFile('index.html')->withContent('this is index.html')->at($root);
        vfsStream::newFile('foo.html')->withContent('this is foo.html')->at($root);
        $this->mockRequest         = $this->getMock('stubbles\input\web\WebRequest');
        $this->mockResponse        = $this->getMock('stubbles\webapp\response\Response');
        $this->htmlFilePassThrough = new HtmlFilePassThrough(vfsStream::url('root'));
    }

    /**
     * @test
     */
    public function functionReturnsClassName()
    {
        $this->assertEquals(
                get_class($this->htmlFilePassThrough),
                \stubbles\webapp\htmlPassThrough()
        );
    }

    /**
     * @test
     */
    public function annotationsPresentOnConstructor()
    {
        $annotations = reflect\constructorAnnotationsOf($this->htmlFilePassThrough);
        $this->assertTrue($annotations->contain('Inject'));
        $this->assertTrue($annotations->contain('Named'));
        $this->assertEquals(
                'stubbles.pages.path',
                $annotations->firstNamed('Named')->getName()
        );
    }

    /**
     * @test
     */
    public function requestForNonExistingFileWritesNotFoundResponse()
    {
        $this->mockResponse->expects($this->once())
                           ->method('notFound');
        $this->mockResponse->expects($this->never())
                           ->method('write');
        $this->htmlFilePassThrough->process($this->mockRequest,
                                            $this->mockResponse,
                                            new UriPath('/', '/doesNotExist.html')
        );
    }

    /**
     * @test
     */
    public function selectsAvailableRoute()
    {
        $this->mockResponse->expects($this->once())
                           ->method('write')
                           ->with($this->equalTo('this is foo.html'));
        $this->htmlFilePassThrough->process($this->mockRequest,
                                            $this->mockResponse,
                                            new UriPath('/', '/foo.html')
        );
    }

    /**
     * @test
     */
    public function fallsBackToIndexFileIfRequestForSlashOnly()
    {
        $this->mockResponse->expects($this->once())
                           ->method('write')
                           ->with($this->equalTo('this is index.html'));
        $this->htmlFilePassThrough->process($this->mockRequest,
                                            $this->mockResponse,
                                            new UriPath('/', '/')
        );
    }
}
