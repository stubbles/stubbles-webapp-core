<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\htmlpassthrough;
use bovigo\callmap\NewInstance;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;
use stubbles\webapp\Request;
use stubbles\webapp\Response;
use stubbles\webapp\UriPath;
use stubbles\webapp\response\Error;

use function bovigo\assert\assertThat;
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
class HtmlFilePassThroughTest extends TestCase
{
    /**
     * @var  \stubbles\webapp\htmlpassthrough\HtmlFilePassThrough
     */
    private $htmlFilePassThrough;
    /**
     * @var  \org\bovigo\vfs\vfsStreamFile
     */
    private $file;

    protected function setUp(): void
    {
        $root = vfsStream::setup();
        vfsStream::newFile('index.html')->withContent('this is index.html')->at($root);
        $this->file = vfsStream::newFile('foo.html');
        $this->file->withContent('this is foo.html')->at($root);
        $this->htmlFilePassThrough = new HtmlFilePassThrough(vfsStream::url('root'));
    }

    /**
     * @test
     */
    public function functionReturnsClassName(): void
    {
        assertThat(
                \stubbles\webapp\htmlPassThrough(),
                equals(get_class($this->htmlFilePassThrough))
        );
    }

    /**
     * @test
     */
    public function annotationsPresentOnConstructor(): void
    {
        $annotations = annotationsOfConstructor($this->htmlFilePassThrough);
        assertTrue($annotations->contain('Named'));
        assertThat(
                $annotations->firstNamed('Named')->getName(),
                equals('stubbles.pages.path')
        );
    }

    /**
     * @test
     */
    public function requestForNonExistingFileWritesNotFoundResponse(): void
    {
        $error = Error::notFound();
        assertThat(
                $this->htmlFilePassThrough->resolve(
                        NewInstance::of(Request::class),
                        NewInstance::of(Response::class)
                                ->returns(['notFound' => $error]),
                        new UriPath('/', '/doesNotExist.html')
                ),
                isSameAs($error)
        );
    }

    /**
     * @test
     * @since  8.0.0
     */
    public function requestForNonReadableFileWritesInternalServerErrorResponse(): void
    {
        $this->file->chmod(0000);
        $error = Error::internalServerError('');
        assertThat(
                $this->htmlFilePassThrough->resolve(
                        NewInstance::of(Request::class),
                        NewInstance::of(Response::class)
                                ->returns(['internalServerError' => $error]),
                        new UriPath('/', '/foo.html')
                ),
                isSameAs($error)
        );
    }

    /**
     * @test
     */
    public function selectsAvailableRoute(): void
    {
        assertThat(
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
    public function fallsBackToIndexFileIfRequestForSlashOnly(): void
    {
        assertThat(
                $this->htmlFilePassThrough->resolve(
                        NewInstance::of(Request::class),
                        NewInstance::of(Response::class),
                        new UriPath('/', '/')
                ),
                equals('this is index.html')
        );
    }
}
