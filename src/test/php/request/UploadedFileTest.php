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
 * Test for stubbles\webapp\request\UploadedFile.
 *
 * @since  8.1.0
 * @group  request
 * @group  upload
 */
class UploadedFileTest extends TestCase
{
    /**
     * @test
     */
    public function returnsGivenName(): void
    {
        $file = new UploadedFile('example.txt', '/tmp/foobarbaz', 303);
        assertThat($file->name(), equals('example.txt'));
    }

    /**
     * @test
     */
    public function returnsGivenSize(): void
    {
        $file = new UploadedFile('example.txt', '/tmp/foobarbaz', 303);
        assertThat($file->size(), equals(303));
    }

    /**
     * @test
     */
    public function returnsActualMimetypeOfFile(): void
    {
        $file = new UploadedFile('example.php', __FILE__, 303);
        assertThat($file->mimetype(), equals('text/x-php'));
    }

    /**
     * @test
     */
    public function failureToDetectMimetypeThrowsMimetypeCheckFailed(): void
    {
        $file = new UploadedFile('example.php', '/tmp/foobarbaz', 303);
        expect(function() use($file) { $file->mimetype(); })
            ->throws(MimetypeCheckFailed::class);
    }

    /**
     * @test
     */
    public function moveUploadedFileReturnsPathAfterMoveAndUsesGivenNameIfNotOverruled(): void
    {
        $file = new class('example.php', '/tmp/foobarbaz', 303) extends UploadedFile {
            public function move_uploaded_file(callable $move_uploaded_file): void
            {
                $this->move_uploaded_file = $move_uploaded_file;
            }
        };
        $file->move_uploaded_file(function() { return true; });
        assertThat($file->move('/target'), equals('/target/example.php'));
    }

    /**
     * @test
     */
    public function moveUploadedFileReturnsPathAfterMoveAndUsesOverruledName(): void
    {
        $file = new class('example.php', '/tmp/foobarbaz', 303) extends UploadedFile {
            public function move_uploaded_file(callable $move_uploaded_file): void
            {
                $this->move_uploaded_file = $move_uploaded_file;
            }
        };
        $file->move_uploaded_file(function() { return true; });
        assertThat($file->move('/target', 'other.php'), equals('/target/other.php'));
    }

    /**
     * @test
     */
    public function moveUploadedThrowsRuntimeExceptionWhenMoveFails(): void
    {
        $file = new class('example.php', '/tmp/foobarbaz', 303) extends UploadedFile {
            public function move_uploaded_file(callable $move_uploaded_file): void
            {
                $this->move_uploaded_file = $move_uploaded_file;
            }
        };
        $file->move_uploaded_file(function() { \trigger_error('some error', \E_USER_NOTICE); return false; });
        expect(function() use ($file) { $file->move('/target'); })
            ->throws(\RuntimeException::class)
            ->withMessage('Could not move uploaded file "example.php": some error');
    }

    /**
     * @test
     */
    public function removeRemovesTmpFile(): void
    {
        $root = vfsStream::setup();
        $tmpPath = vfsStream::newFile('foobarbaz')->at($root)->url();
        $file = new UploadedFile('example.php', $tmpPath, 303);
        assertTrue($file->remove());
        assertFalse($root->hasChild('foobarbaz'));
    }

    /**
     * @test
     */
    public function failureOnRemoveThrowsRuntimeException(): void
    {
        $root = vfsStream::setup();
        $file = new UploadedFile('example.php', 'vfs://root/foobarbaz', 303);
        expect(function() use ($file) { $file->remove(); })
            ->throws(\RuntimeException::class)
            ->withMessage('Could not remove uploaded file "example.php": unlink(vfs://root/foobarbaz): No such file or directory');
    }
}