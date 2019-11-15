<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\helper\response\mimetypes;
/**
 * Helper class for the test.
 */
class AsArray
{
    public function asArray(): array
    {
        return ['column1' => 'foo', 'column2' => 'bar'];
    }
}