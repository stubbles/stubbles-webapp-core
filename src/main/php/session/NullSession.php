<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\session;
use stubbles\webapp\session\id\SessionId;
/**
 * Null session for usages in non-sessionbased web applications.
 *
 * @since  2.0.0
 */
class NullSession implements Session
{
    public function __construct(private SessionId $id) { }

    public function isNew(): bool
    {
        return true;
    }

    public function id(): string
    {
        return (string) $this->id;
    }

    public function regenerateId(): Session
    {
        $this->id->regenerate();
        return $this;
    }

    public function name(): string
    {
        return $this->id->name();
    }

    public function isValid(): bool
    {
        return true;
    }

    public function invalidate(): Session
    {
        $this->id->invalidate();
        return $this;
    }

    public function hasValue(string $key): bool
    {
        return false;
    }

    public function value(string $key, mixed $default = null): mixed
    {
        return $default;
    }

    public function putValue(string $key, mixed $value): void
    {
        // intentionally empty
    }

    public function removeValue(string $key): bool
    {
        return false;
    }

    /**
     * @return  string[]
     */
    public function valueKeys(): array
    {
        return [];
    }
}
