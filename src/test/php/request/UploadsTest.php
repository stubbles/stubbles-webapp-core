<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\request;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

use function bovigo\assert\{assertFalse, assertThat, assertTrue, expect};
use function bovigo\assert\predicate\equals;
/**
 * Test for stubbles\webapp\request\Uploads.
 *
 * @since  8.1.0
 * @group  request
 * @group  upload
 */
class UploadsTest extends TestCase
{
    /**
     * @test
     */
    public function containReturnsFalseIfNoUploadForGivenFieldPresent(): void
    {
        $uploads = new Uploads([]);
        assertFalse($uploads->contain('example'));
    }

    /**
     * @test
     */
    public function containReturnsTrueIfUploadsForGivenFieldPresent(): void
    {
        $uploads = new Uploads(['example' => []]);
        assertTrue($uploads->contain('example'));
    }

    public function _FILES(): array
    {
        return [
            'no uploads' => [[], 0],
            'single upload field' => [['example' => ['name' => 'foo.txt']], 1],
            'multiple upload field with single upload' => [['example' => ['name' => ['foo.txt']]], 1],
            'multiple upload field with multiple uploads' => [['example' => ['name' => ['foo.txt', 'bar.txt']]], 2],
        ];
    }

    /**
     * @test
     * @dataProvider _FILES
     */
    public function amountIs0IfNoUploadForGivenFieldPresent(array $_files, int $expected): void
    {
        $uploads = new Uploads($_files);
        assertThat($uploads->amount('example'), equals($expected));
    }

    /**
     * @test
     */
    public function selectWhenNoUploadForGivenFieldPresentThrowsNoSuchUpload(): void
    {
        $uploads = new Uploads([]);
        expect(function() use ($uploads) { $uploads->select('example'); })
            ->throws(NoSuchUpload::class)
            ->withMessage('No file uploaded under key "example"');
    }

    /**
     * @test
     */
    public function selectWhenNoUploadForGivenFieldAtGivenPositionPresentThrowsNoSuchUpload(): void
    {
        $uploads = new Uploads(['example' => ['name' => ['foo.txt']]]);
        expect(function() use ($uploads) { $uploads->select('example', 2); })
            ->throws(NoSuchUpload::class)
            ->withMessage('No file uploaded under key "example" at position 2');
    }

    /**
     * @test
     */
    public function selectForSingleUploadFieldWithoutErrorReturnsUploadedFile(): void
    {
        $uploads = new Uploads(['example' => [
            'name'     => 'foo.txt',
            'tmp_name' => '/tmp/foobarbar',
            'size'     => 303,
            'error'    => \UPLOAD_ERR_OK
        ]]);
        assertThat(
            $uploads->select('example'),
            equals(new UploadedFile('foo.txt', '/tmp/foobarbar', 303))
        );
    }

    /**
     * @test
     */
    public function selectForSingleUploadFieldWithErrorThrowsUploadFailed(): void
    {
        $uploads = new Uploads(['example' => [
            'name'     => 'foo.txt',
            'tmp_name' => '/tmp/foobarbar',
            'size'     => 303,
            'error'    => \UPLOAD_ERR_NO_FILE
        ]]);
        expect(function() use ($uploads) { $uploads->select('example'); })
            ->throws(UploadFailed::class)
            ->withMessage('No file was uploaded');
    }

    /**
     * @test
     */
    public function selectForMulitpleUploadFieldWithoutErrorReturnsUploadedFile(): void
    {
        $uploads = new Uploads(['example' => [
            'name'     => ['foo.txt', 'bar.txt'],
            'tmp_name' => ['/tmp/foobarbar', '/tmp/anotherOne'],
            'size'     => [303, 313],
            'error'    => [\UPLOAD_ERR_OK, \UPLOAD_ERR_NO_FILE]
        ]]);
        assertThat(
            $uploads->select('example', 0),
            equals(new UploadedFile('foo.txt', '/tmp/foobarbar', 303))
        );
    }

    /**
     * @test
     */
    public function selectForMulitpleUploadFieldWithErrorThrowsUploadFailed(): void
    {
        $uploads = new Uploads(['example' => [
            'name'     => ['foo.txt', 'bar.txt'],
            'tmp_name' => ['/tmp/foobarbar', ''],
            'size'     => [303, 0],
            'error'    => [\UPLOAD_ERR_OK, \UPLOAD_ERR_NO_FILE]
        ]]);
        expect(function() use ($uploads) { $uploads->select('example', 1); })
            ->throws(UploadFailed::class)
            ->withMessage('No file was uploaded');
    }
}