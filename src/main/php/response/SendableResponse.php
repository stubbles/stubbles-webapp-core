<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\response;
use stubbles\streams\OutputStream;
/**
 * Final response after webapp has processed the request.
 *
 * @since  4.0.0
 */
interface SendableResponse
{
    /**
     * returns status code of response
     *
     * @return  int
     */
    public function statusCode(): int;

    /**
     * check if response contains a certain header
     *
     * @param   string  $name   name of header to check
     * @param   string  $value  optional  if given the value is checked as well
     * @return  bool
     */
    public function containsHeader(string $name, string $value = null): bool;

    /**
     * checks if response contains a certain cookie
     *
     * @param   string  $name   name of cookie to check
     * @param   string  $value  optional  if given the value is checked as well
     * @return  bool
     */
    public function containsCookie(string $name, string $value = null): bool;

    /**
     * sends response
     *

     * In case no output stream is passed it will create a
     * stubbles\streams\StandardOutputStream where the response body will be
     * written to.
     * The output stream is returned. In case no output stream was passed and
     * the request doesn't allow a response body or no resource for the response
     * body was set the return value is null because no standard stream will be
     * created in such a case.
     *
     * @param   \stubbles\streams\OutputStream  $out  optional  where to write response body to
     * @return  \stubbles\streams\OutputStream|null
     */
    public function send(OutputStream $out = null);
}
