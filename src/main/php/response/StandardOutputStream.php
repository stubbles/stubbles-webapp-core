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
use stubbles\streams\ResourceOutputStream;
/**
 * Output stream for writing to standard output.
 *
 * @internal
 */
class StandardOutputStream extends ResourceOutputStream
{
    /**
     * constructor
     */
    public function __construct()
    {
        $this->setHandle(fopen('php://output', 'w'));
    }
}

