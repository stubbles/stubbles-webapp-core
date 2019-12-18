<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\request;
/**
 * Represents a single uploaded file.
 *
 * @since  8.1.0
 */
class UploadedFile
{
    /**
     * @var  string
     */
    private $name;
    /**
     * @var  string
     */
    private $tmpName;
    /**
     * @var  int
     */
    private $size;
    /**
     * @var  string
     */
    private $mimeType;
    /**
     * @var  string
     */
    private $mimeTypeError;
    /**
     * @var  callable
     */
    protected $move_uploaded_file = '\move_uploaded_file';

    /**
     * constructor
     *
     * @param  string  $name     original file name
     * @param  string  $tmpName  path to uploaded file
     * @param  int     $size     size of uploaded file in bytes
     */
    public function __construct(string $name, string $tmpName, int $size)
    {
        $this->name    = $name;
        $this->tmpName = $tmpName;
        $this->size    = $size;
    }

    /**
     * Returns original file name.
     *
     * @api
     * @return  string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Returns size of uploaded file in bytes.
     *
     * @api
     * @return  int
     */
    public function size(): int
    {
        return $this->size;
    }

    /**
     * Detects mimetype.
     *
     * This doesn't rely on the information provided by the useragent during
     * upload, but checks the actual file for the mimetype using the fileinfo
     * extension.
     *
     * @api
     * @return  string
     * @throws  MimetypeCheckFailed  in case mime type detection fails
     */
    public function mimetype(): string
    {
        if (null !== $this->mimeTypeError) {
            throw new MimetypeCheckFailed($this->mimeTypeError);
        }

        if (null !== $this->mimeType) {
            return $this->mimeType;
        }

        $mimeType = @\mime_content_type($this->tmpName);
        if (false === $mimeType) {
            $error = \error_get_last();
            $msg = $error['message'] ?? 'unknown error while trying to detect mimetype';
            $this->mimeTypeError = 'Could not detect mimetype of uploaded file: ' . $msg;
            throw new MimetypeCheckFailed($this->mimeTypeError);
        }

        $this->mimeType = $mimeType;
        return $this->mimeType;
    }

    /**
     * Moves file to target destination.
     *
     * Please note that a file can only be moved once.
     *
     * @api
     * @param   string       $targetDirectory  directory where to move file to
     * @param   string|null  $fileName         optional  name of file if name supplied by upload shouldn't be used
     * @return  string       full path of moved uploaded file
     * @throws  \RuntimeException  in case moving fails
     */
    public function move(string $targetDirectory, string $fileName = null): string
    {
        $targetFile = $targetDirectory . \DIRECTORY_SEPARATOR . ($fileName ?? $this->name);
        $move_uploaded_file = $this->move_uploaded_file;
        if (@$move_uploaded_file($this->tmpName, $targetFile)) {
            return $targetFile;
        }

        $error = \error_get_last();
        $msg = $error['message'] ?? 'unknown error while trying to move uploaded file';
        throw new \RuntimeException(\sprintf(
            'Could not move uploaded file "%s": %s', $this->name, $msg
        ));
    }

    /**
     * Removes uploaded file.
     *
     * @api
     * @return  bool
     * @throws  \RuntimeException  in case removal fails
     */
    public function remove(): bool
    {
        if (@\unlink($this->tmpName)) {
            return true;
        }

        $error = \error_get_last();
        $msg = $error['message'] ?? 'unknown error';
        throw new \RuntimeException(\sprintf(
            'Could not remove uploaded file "%s": %s', $this->name, $msg
        ));
    }
}