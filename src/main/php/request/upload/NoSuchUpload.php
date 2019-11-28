<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\request\upload;
/**
 * Exception in case an UploadedFile is requested but not present.
 *
 * @since  8.1.0
 */
class NoSuchUpload extends \Exception
{
    // intentionally empty
}