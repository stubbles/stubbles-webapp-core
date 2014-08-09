<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\auth\token;
use stubbles\webapp\auth\Token;
use stubbles\input\Filter;
use stubbles\input\filter\ReusableFilter;
use stubbles\input\Param;
/**
 * Filters token value from param.
 *
 * @since  5.0.0
 */
class TokenFilter implements Filter
{
    use ReusableFilter;

    /**
     * apply filter on given param
     *
     * @param   \stubbles\input\Param  $param
     * @return  \stubbles\webapp\auth\Token
     */
    public function apply(Param $param)
    {
        if ($param->isEmpty()) {
            return new Token(null);
        }

        $value = $param->value();
        if (strtolower(trim(substr($value, 0, 7))) === 'bearer') {
            return new Token(substr($value, 7));
        }

        return new Token($value);
    }
}
