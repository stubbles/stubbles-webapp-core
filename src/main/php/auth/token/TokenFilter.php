<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\auth\token;
use stubbles\webapp\auth\Token;
use stubbles\input\Filter;
use stubbles\input\filter\ReusableFilter;
use stubbles\values\Value;
/**
 * Filters token value from param.
 *
 * @since  5.0.0
 */
class TokenFilter extends Filter
{
    use ReusableFilter;

    /**
     * apply filter on given value
     *
     * @return  mixed[]
     */
    public function apply(Value $value): array
    {
        if ($value->isEmpty()) {
            return $this->filtered(new Token(null));
        }

        $value = $value->value();
        if (strtolower(trim(substr($value, 0, 7))) === 'bearer') {
            return $this->filtered(new Token(substr($value, 7)));
        }

        return $this->filtered(new Token($value));
    }
}
