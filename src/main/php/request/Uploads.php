<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\request;
use stubbles\input\errors\ParamError;
/**
 * Provides access to uploaded files.
 *
 * @since  8.1.0
 */
class Uploads
{
    /**
     * map of error codes and according param error ids
     *
     * @type  array<int,string>
     */
    private static $paramErrorId = [
        \UPLOAD_ERR_INI_SIZE   => 'UPLOAD_EXCEEDS_MAXSIZE_ALLOWED_BY_SERVER',
        \UPLOAD_ERR_FORM_SIZE  => 'UPLOAD_EXCEEDS_MAXSIZE_ALLOWED_BY_FORM',
        \UPLOAD_ERR_PARTIAL    => 'UPLOAD_NOT_COMPLETED',
        \UPLOAD_ERR_NO_FILE    => 'UPLOAD_MISSING',
    ];
    /**
     * @type  array<string,array>
     */
    private $uploads;

    /**
     * constructor
     *
     * @param  array<string,array>  $uploads  see description of $_FILES for expected structure
     */
    public function __construct(array $uploads)
    {
        $this->uploads = $uploads;
    }

    /**
     * Checks whether a file was uploaded under given field name.
     *
     * @api
     * @param   string  $field  name of field to select file from
     * @return  bool
     */
    public function contain(string $field): bool
    {
        return isset($this->uploads[$field]);
    }

    /**
     * Returns amount of uploaded files available for given field name.
     *
     * @api
     * @param   string  $field  name of field to select upload from
     * @return  int     amount of uploaded files available for given field name
     */
    public function amount(string $field): int
    {
        if (!$this->contain($field)) {
            return 0;
        }

        if (is_array($this->uploads[$field]['name'])) {
            return count($this->uploads[$field]['name']);
        }

        return 1;
    }

    private function isUserError(int $error): bool
    {
        return $error === \UPLOAD_ERR_INI_SIZE
            || $error === \UPLOAD_ERR_FORM_SIZE
            || $error === \UPLOAD_ERR_PARTIAL
            || $error === \UPLOAD_ERR_NO_FILE;
    }

    /**
     * Returns a ParamError when a user error for the upload exists.
     *
     * A user error can occur when the uploaded file exceeds the size permitted by either
     * upload_max_filesize directive in php.ini, MAX_FILE_SIZE directive in form, or was
     * only partially or not uploaded at all.
     *
     * This method will return null even if there is an error but that is due a problem
     * on the server side.
     *
     * @api
     * @param   string  $field  name of field to select upload from
     * @param   int     $pos    upload position in case of multiple uploads in same field
     * @return  ParamError|null
     */
    public function errorFor(string $field, int $pos = 0): ?ParamError
    {
        if (!$this->contain($field)) {
            return null;
        }

        if (!is_array($this->uploads[$field]['error']) && $this->isUserError($this->uploads[$field]['error'])) {
            return new ParamError(self::$paramErrorId[$this->uploads[$field]['error']]);
        }

        if (!isset($this->uploads[$field]['error'][$pos]) || !$this->isUserError($this->uploads[$field]['error'][$pos])) {
            return null;
        }

        return new ParamError(self::$paramErrorId[$this->uploads[$field]['error'][$pos]]);
    }

    /**
     * Returns upload for given field and position.
     *
     * For single upload fields the position is ignored.
     *
     * @api
     * @param   string  $field  name of field to select upload from
     * @param   int     $pos    upload position in case of multiple uploads in same field
     * @return  UploadedFile
     * @throws  NoSuchUpload  in case no upload available from given field or position
     * @throws  UploadFailed  in case the upload couldn't be completed
     */
    public function select(string $field, int $pos = 0): UploadedFile
    {
        if (!$this->contain($field)) {
            throw new NoSuchUpload(sprintf('No file uploaded under key "%s"', $field));
        }

        // for single file uploads the field data are scalar values, and arrays otherwise
        if (!is_array($this->uploads[$field]['name'])) {
            if (\UPLOAD_ERR_OK != $this->uploads[$field]['error']) {
                throw new UploadFailed($this->uploads[$field]['name'], $this->uploads[$field]['error']);
            }

            return new UploadedFile(
                $this->uploads[$field]['name'],
                $this->uploads[$field]['tmp_name'],
                $this->uploads[$field]['size']
            );
        }

        if (!isset($this->uploads[$field]['name'][$pos])) {
            throw new NoSuchUpload(sprintf(
                'No file uploaded under key "%s" at position %d',
                $field,
                $pos
            ));
        }

        if (\UPLOAD_ERR_OK != $this->uploads[$field]['error'][$pos]) {
            throw new UploadFailed($this->uploads[$field]['name'][$pos], $this->uploads[$field]['error'][$pos]);
        }

        return new UploadedFile(
            $this->uploads[$field]['name'][$pos],
            $this->uploads[$field]['tmp_name'][$pos],
            $this->uploads[$field]['size'][$pos]
        );
    }
}