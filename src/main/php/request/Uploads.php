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
 * Provides access to uploaded files.
 *
 * @since  8.1.0
 */
class Uploads
{
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