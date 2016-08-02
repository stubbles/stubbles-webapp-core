<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\htmlpassthrough;
use bovigo\callmap\NewInstance;
use org\bovigo\vfs\vfsStream;
use stubbles\webapp\Request;
use stubbles\webapp\Response;
use stubbles\webapp\UriPath;
use stubbles\webapp\response\Error;

use function bovigo\assert\assert;
use function bovigo\assert\assertTrue;
use function bovigo\assert\predicate\equals;
use function bovigo\assert\predicate\isSameAs;
use function stubbles\reflect\annotationsOfConstructor;
/**
 * Test for stubbles\webapp\htmlpassthrough\HtmlFilePassThrough.
 *
 * @group  htmlpassthrough
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
     * set up the test environment
     */
    public function setUp()
    {
        $root = vfsStream::setup();
        vfsStream::newFile('index.html')->withContent('this is index.html')->at($root);
        vfsStream::newFile('foo.html')->withContent('this is foo.html')->at($root);
        $this->htmlFilePassThrough = new HtmlFilePassThrough(vfsStream::url('root'));
    }

    /**
     * @test
     */
    public function functionReturnsClassName()
    {
        assert(
                \stubbles\webapp\htmlPassThrough(),
                equals(get_class($this->htmlFilePassThrough))
        );
    }

    /**
     * @test
     */
    public function annotationsPresentOnConstructor()
    {
        $annotations = annotationsOfConstructor($this->htmlFilePassThrough);
        assertTrue($annotations->contain('Named'));
        assert(
                $annotations->firstNamed('Named')->getName(),
                equals('stubbles.pages.path')
        );
    }

    /**
     * @test
     */
    public function requestForNonExistingFileWritesNotFoundResponse()
    {
        $error = Error::notFound();
        assert(
                $this->htmlFilePassThrough->resolve(
                        NewInstance::of(Request::class),
                        NewInstance::of(Response::class)
                                ->mapCalls(['notFound' => $error]),
                        new UriPath('/', '/doesNotExist.html')
                ),
                isSameAs($error)
        );
    }

    /**
     * @test
     */
    public function selectsAvailableRoute()
    {
        assert(
                $this->htmlFilePassThrough->resolve(
                        NewInstance::of(Request::class),
                        NewInstance::of(Response::class),
                        new UriPath('/', '/foo.html')
                ),
                equals('this is foo.html')
        );
    }

    /**
     * @test
     */
    public function fallsBackToIndexFileIfRequestForSlashOnly()
    {
        assert(
                $this->htmlFilePassThrough->resolve(
                        NewInstance::of(Request::class),
                        NewInstance::of(Response::class),
                        new UriPath('/', '/')
                ),
                equals('this is index.html')
        );
    }
}
