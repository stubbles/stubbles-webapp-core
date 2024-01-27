<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\request;

use Exception;

/**
 * Exception in case an error happened during upload.
 *
 * @since  8.1.0
 */
class UploadFailed extends Exception
{
    /**
     * map of error codes and according messages
     *
     * @var  array<int,string>
     */
    private static array $msg = [
        \UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        \UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
        \UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded',
        \UPLOAD_ERR_NO_FILE    => 'No file was uploaded',
        \UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
        \UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        \UPLOAD_ERR_EXTENSION  => 'File upload stopped by extension',
    ];

    public function __construct(private string $filename, int $errorCode)
    {
        parent::__construct(self::$msg[$errorCode] ?? 'Unknown upload error', $errorCode);
    }

    /**
     * Returns name of file for which upload failed.
     */
    public function filename(): string
    {
        return $this->filename;
    }
}