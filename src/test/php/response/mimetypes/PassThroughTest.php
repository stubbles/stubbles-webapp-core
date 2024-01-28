<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\response\mimetypes;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stubbles\streams\memory\MemoryOutputStream;
use stubbles\webapp\response\Error;

use function bovigo\assert\assertThat;
use function bovigo\assert\predicate\equals;
/**
 * Tests for stubbles\webapp\response\mimetypes\PassThrough.
 *
 * @since  6.0.0
 */
#[Group('response')]
#[Group('mimetypes')]
class PassThroughTest extends TestCase
{
    private PassThrough $passThrough;

    protected function setUp(): void
    {
        $this->passThrough = new PassThrough();
    }

    #[Test]
    public function defaultMimeType(): void
    {
        assertThat((string) $this->passThrough, equals('text/html'));
    }

    #[Test]
    public function mimeTypeCanBeSpecialised(): void
    {
        assertThat(
            (string) $this->passThrough->specialise('text/plain'),
            equals('text/plain')
        );
    }

    #[Test]
    public function serializesPassesThroughString(): void
    {
        assertThat(
            $this->passThrough->serialize(
                'some string',
                new MemoryOutputStream()
            )->buffer(),
            equals('some string')
        );
    }

    #[Test]
    public function serializesHandlesErrorAsString(): void
    {
        assertThat(
            $this->passThrough->serialize(
                new Error('some error message'),
                new MemoryOutputStream()
            )->buffer(),
            equals('Error: some error message')
        );
    }
}
