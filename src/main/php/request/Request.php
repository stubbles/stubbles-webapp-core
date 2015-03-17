<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\request;
/**
 * Interface for web applications requests.
 *
 * @api
 */
interface Request extends \stubbles\input\web\WebRequest
{
    /**
     * returns an input stream which allows to read the request body
     *
     * It returns the data raw and unsanitized, any filtering and validating
     * must be done by the caller.
     *
     * @since   5.3.0
     * @return  \stubbles\streams\InputStream
     */
    public function body();
}
