<?php

declare(strict_types=1);

namespace EinarHansen\Cache;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Psr\Cache\CacheItemInterface;

class CacheItem implements CacheItemInterface
{
    private string $key;

    private mixed $value;

    private bool $hit;

    private ?DateTimeInterface $expires = null;

    public function __construct(string $key, mixed $value = null, bool $hit = false)
    {
        $this->key = $key;
        $this->hit = $hit;
        $this->value = $this->hit ? $value : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * {@inheritdoc}
     */
    public function get(): mixed
    {
        if (! $this->isHit()) {
            return null;
        }

        return $this->value;
    }

    /**
     * A cache hit occurs when a Calling Library requests an Item by key
     * and a matching value is found for that key, and that value has
     * not expired, and the value is not invalid for some other reason.
     *
     * Calling Libraries SHOULD make sure to verify isHit() on all get() calls.
     *
     * {@inheritdoc}
     */
    public function isHit(): bool
    {
        if (! $this->hit) {
            return false;
        }
        if (is_null($this->expires)) {
            return true;
        }

        return $this->expires > new DateTimeImmutable();
    }

    /**
     * {@inheritdoc}
     */
    public function set(mixed $value): static
    {
        $this->value = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function expiresAt(?DateTimeInterface $expires): static
    {
        if ($expires instanceof DateTimeInterface && ! $expires instanceof DateTimeImmutable) {
            $timezone = $expires->getTimezone();
            $expires = DateTimeImmutable::createFromFormat('U', (string) $expires->getTimestamp(), $timezone);
            if ($expires) {
                $expires = $expires->setTimezone($timezone);
            }
        }

        if ($expires instanceof DateTimeInterface) {
            $this->expires = $expires;
        } else {
            $this->expires = null;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function expiresAfter(int|DateInterval|null $time): static
    {
        if ($time === null) {
            $this->expires = null;

            return $this;
        }

        $this->expires = new DateTimeImmutable();

        if (! $time instanceof DateInterval) {
            $time = new DateInterval(sprintf('PT%sS', $time));
        }

        $this->expires = $this->expires->add($time);

        return $this;
    }

    public function getExpiresAt(): ?DateTimeInterface
    {
        return $this->expires;
    }
}
