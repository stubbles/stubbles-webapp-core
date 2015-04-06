<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\htmlpassthrough;
use org\bovigo\vfs\vfsStream;
use stubbles\lang\reflect;
use stubbles\webapp\UriPath;
use stubbles\webapp\response\Error;
/**
 * Test for stubbles\webapp\htmlpassthrough\HtmlFilePassThrough.
 *
 * @group  processor
 * @since  4.0.0
 */
class HtmlFilePassThroughTest extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  \stubbles\webapp\htmlpassthrough\HtmlFilePassThrough
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
        $this->mockRequest         = $this->getMock('stubbles\webapp\Request');
        $this->mockResponse        = $this->getMock('stubbles\webapp\Response');
        $this->htmlFilePassThrough = new HtmlFilePassThrough(vfsStream::url('root'));
    }

    /**
     * @test
     */
    public function functionReturnsClassName()
    {
        assertEquals(
                get_class($this->htmlFilePassThrough),
                \stubbles\webapp\htmlPassThrough()
        );
    }

    /**
     * @test
     */
    public function annotationsPresentOnConstructor()
    {
        $annotations = reflect\annotationsOfConstructor($this->htmlFilePassThrough);
        assertTrue($annotations->contain('Inject'));
        assertTrue($annotations->contain('Named'));
        assertEquals(
                'stubbles.pages.path',
                $annotations->firstNamed('Named')->getName()
        );
    }

    /**
     * @test
     */
    public function requestForNonExistingFileWritesNotFoundResponse()
    {
        $error = Error::notFound();
        $this->mockResponse->expects($this->once())
                           ->method('notFound')
                           ->will($this->returnValue($error));
        assertSame(
                $error,
                $this->htmlFilePassThrough->resolve(
                        $this->mockRequest,
                        $this->mockResponse,
                        new UriPath('/', '/doesNotExist.html')
                )
        );
    }

    /**
     * @test
     */
    public function selectsAvailableRoute()
    {
        assertEquals(
                'this is foo.html',
                $this->htmlFilePassThrough->resolve(
                        $this->mockRequest,
                        $this->mockResponse,
                        new UriPath('/', '/foo.html')
                )
        );
    }

    /**
     * @test
     */
    public function fallsBackToIndexFileIfRequestForSlashOnly()
    {
        assertEquals(
                'this is index.html',
                $this->htmlFilePassThrough->resolve(
                        $this->mockRequest,
                        $this->mockResponse,
                        new UriPath('/', '/')
                )
        );
    }
}
