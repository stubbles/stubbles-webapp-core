<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\response\mimetypes;
use PHPUnit\Framework\TestCase;
use stubbles\streams\memory\MemoryOutputStream;

use function bovigo\assert\assertThat;
use function bovigo\assert\predicate\equals;
/**
 * Tests for stubbles\webapp\response\mimetypes\Json.
 *
 * @group  response
 * @group  mimetypes
 * @since  6.0.0
 */
class JsonTest extends TestCase
{
    /**
     * @var  \stubbles\webapp\response\mimetypes\Json
     */
    private $json;

    protected function setUp(): void
    {
        $this->json = new Json();
    }

    /**
     * @test
     */
    public function defaultMimeType(): void
    {
        assertThat((string) $this->json, equals('application/json'));
    }

    /**
     * @test
     */
    public function mimeTypeCanBeSpecialised(): void
    {
        assertThat(
                (string) $this->json->specialise('text/json'),
                equals('text/json')
        );
    }

    /**
     * @test
     */
    public function serializesResourceToJson(): void
    {
        assertThat(
                $this->json->serialize(
                        ['foo', 'bar' => 313],
                        new MemoryOutputStream()
                )->buffer(),
                equals(json_encode(['foo', 'bar' => 313], JSON_THROW_ON_ERROR))
        );
    }
}
