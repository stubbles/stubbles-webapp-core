<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\request;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use stubbles\input\errors\ParamError;

use function bovigo\assert\{assertFalse, assertNull, assertThat, assertTrue, expect};
use function bovigo\assert\predicate\equals;
/**
 * Test for stubbles\webapp\request\Uploads.
 *
 * @since  8.1.0
 */
#[Group('request')]
#[Group('upload')]
class UploadsTest extends TestCase
{
    #[Test]
    public function containReturnsFalseIfNoUploadForGivenFieldPresent(): void
    {
        $uploads = new Uploads([]);
        assertFalse($uploads->contain('example'));
    }

    #[Test]
    public function containReturnsTrueIfUploadsForGivenFieldPresent(): void
    {
        $uploads = new Uploads(['example' => []]);
        assertTrue($uploads->contain('example'));
    }

    public static function _FILES(): Generator
    {
        yield 'no uploads' => [[], 0];
        yield 'single upload field' => [['example' => ['name' => 'foo.txt']], 1];
        yield 'multiple upload field with single upload' => [['example' => ['name' => ['foo.txt']]], 1];
        yield 'multiple upload field with multiple uploads' => [['example' => ['name' => ['foo.txt', 'bar.txt']]], 2];
    }

    /**
     * @param  array<string,array<string,scalar>>  $_files
     */
    #[Test]
    #[DataProvider('_FILES')]
    public function amountIsNumberOfUploadedFiles(array $_files, int $expected): void
    {
        $uploads = new Uploads($_files);
        assertThat($uploads->amount('example'), equals($expected));
    }

    #[Test]
    public function hasNoErrorWhenNoUploadForGivenFieldPresent(): void
    {
        $uploads = new Uploads([]);
        assertNull($uploads->errorFor('example'));
    }

    #[Test]
    public function hasNoErrorWhenNoUploadForGivenFieldPresentForMultiple(): void
    {
        $uploads = new Uploads(['example' => ['name' => ['foo.txt'], 'error' => [\UPLOAD_ERR_OK]]]);
        assertNull($uploads->errorFor('example', 2));
    }

    public static function noUserErrors(): Generator
    {
        yield [UPLOAD_ERR_NO_TMP_DIR];
        yield [UPLOAD_ERR_CANT_WRITE];
        yield [UPLOAD_ERR_EXTENSION];
        yield [10];
    }

    #[Test]
    #[DataProvider('noUserErrors')]
    public function hasNoErrorWhenNotUserError(int $noUserError): void
    {
        $uploads = new Uploads(['example' => ['name' => 'foo.txt', 'error' => [$noUserError]]]);
        assertNull($uploads->errorFor('example'));
    }

    #[Test]
    #[DataProvider('noUserErrors')]
    public function hasNoErrorWhenNotUserErrorMultiple(int $noUserError): void
    {
        $uploads = new Uploads(['example' => ['name' => ['foo.txt'], 'error' => [$noUserError]]]);
        assertNull($uploads->errorFor('example'));
    }

    public static function userErrors(): Generator
    {
        yield [UPLOAD_ERR_INI_SIZE, new ParamError('UPLOAD_EXCEEDS_MAXSIZE_ALLOWED_BY_SERVER')];
        yield [UPLOAD_ERR_FORM_SIZE, new ParamError('UPLOAD_EXCEEDS_MAXSIZE_ALLOWED_BY_FORM')];
        yield [UPLOAD_ERR_PARTIAL, new ParamError('UPLOAD_NOT_COMPLETED')];
        yield [UPLOAD_ERR_NO_FILE, new ParamError('UPLOAD_MISSING')];
    }

    #[Test]
    #[DataProvider('userErrors')]
    public function hasErrorWhenUserError(int $userError, ParamError $expected): void
    {
        $uploads = new Uploads(['example' => ['name' => ['foo.txt'], 'error' => [$userError]]]);
        assertThat($uploads->errorFor('example'), equals($expected));
    }

    #[Test]
    #[DataProvider('userErrors')]
    public function hasErrorWhenUserErrorMultiple(int $userError, ParamError $expected): void
    {
        $uploads = new Uploads(['example' => ['name' => 'foo.txt', 'error' => $userError]]);
        assertThat($uploads->errorFor('example'), equals($expected));
    }

    #[Test]
    public function selectWhenNoUploadForGivenFieldPresentThrowsNoSuchUpload(): void
    {
        $uploads = new Uploads([]);
        expect(function() use ($uploads) { $uploads->select('example'); })
            ->throws(NoSuchUpload::class)
            ->withMessage('No file uploaded under key "example"');
    }

    #[Test]
    public function selectWhenNoUploadForGivenFieldAtGivenPositionPresentThrowsNoSuchUpload(): void
    {
        $uploads = new Uploads(['example' => ['name' => ['foo.txt']]]);
        expect(function() use ($uploads) { $uploads->select('example', 2); })
            ->throws(NoSuchUpload::class)
            ->withMessage('No file uploaded under key "example" at position 2');
    }

    #[Test]
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

    #[Test]
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

    #[Test]
    public function selectForMultipleUploadFieldWithoutErrorReturnsUploadedFile(): void
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

    #[Test]
    public function selectForMultipleUploadFieldWithErrorThrowsUploadFailed(): void
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