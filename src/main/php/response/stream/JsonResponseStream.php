<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\response\stream;
/**
 * Response stream which delivers application/json.
 */
class JsonResponseStream extends ResponseStream
{
    /**
     * stream the given resource
     *
     * @param  mixed  $resource
     */
    public function stream($resource)
    {
        $this->write(json_encode($resource));
    }
}
