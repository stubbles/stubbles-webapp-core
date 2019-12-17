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
 * Exception in case an error happened during upload.
 *
 * @since  8.1.0
 */
class UploadFailed extends \Exception
{
    /**
     * map of error codes and according messages
     *
     * @var  array<int,string>
     */
    private static $msg = [
        \UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        \UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
        \UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded',
        \UPLOAD_ERR_NO_FILE    => 'No file was uploaded',
        \UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
        \UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        \UPLOAD_ERR_EXTENSION  => 'File upload stopped by extension',
    ];
    /**
     * @var  string
     */
    private $filename;

    /**
     * constructor
     *
     * @param  string  $filename  name of file for which upload failed
     * @param  int     $error     error code
     */
    public function __construct(string $filename, int $error)
    {
        parent::__construct(self::$msg[$error] ?? 'Unknown upload error', $error);
        $this->filename = $filename;
    }

    /**
     * Returns name of file for which upload failed.
     *
     * @return  string
     */
    public function filename(): string
    {
        return $this->filename;
    }
}