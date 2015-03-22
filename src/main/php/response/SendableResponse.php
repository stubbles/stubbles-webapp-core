<?php
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
    public function statusCode();

    /**
     * check if response contains a certain header
     *
     * @param   string  $name   name of header to check
     * @param   string  $value  optional  if given the value is checked as well
     * @return  bool
     */
    public function containsHeader($name, $value = null);

    /**
     * checks if response contains a certain cookie
     *
     * @param   string  $name   name of cookie to check
     * @param   string  $value  optional  if given the value is checked as well
     * @return  bool
     */
    public function containsCookie($name, $value = null);

    /**
     * sends response
     *
     * @param  \stubbles\streams\OutputStream  $out  optional
     */
    public function send(OutputStream $out = null);
}
