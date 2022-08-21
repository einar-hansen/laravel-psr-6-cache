<?php

declare(strict_types=1);

namespace EinarHansen\Cache;

use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Cache\Repository;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Throwable;

class CacheItemPool implements CacheItemPoolInterface
{
    private Repository $repository;

    /**
     * @var \Psr\Cache\CacheItemInterface[]
     */
    private array $deferred = [];

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * {@inheritdoc}
     */
    public function getItem(string $key): CacheItemInterface
    {
        $this->validateKey($key);

        if (isset($this->deferred[$key])) {
            return clone $this->deferred[$key];
        } elseif ($this->repository->has($key)) {
            return new CacheItem($key, $this->repository->get($key), true);
        }

        return new CacheItem($key, null, false);
    }

    /**
     * {@inheritdoc}
     *
     * @return iterable<string, \Psr\Cache\CacheItemInterface>
     */
    public function getItems(array $keys = []): iterable
    {
        return array_combine($keys, array_map(function ($key) {
            return $this->getItem($key);
        }, $keys));
    }

    /**
     * {@inheritdoc}
     */
    public function hasItem(string $key): bool
    {
        $this->validateKey($key);

        if (isset($this->deferred[$key])) {
            $item = $this->deferred[$key];

            return $item->isHit();
        }

        return $this->repository->has($key);
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        try {
            $this->deferred = [];
            $this->repository->getStore()->flush();
        } catch (Throwable $exception) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItem(string $key): bool
    {
        $this->validateKey($key);

        unset($this->deferred[$key]);

        if (! $this->hasItem($key)) {
            return true;
        }

        return $this->repository->forget($key);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItems(array $keys): bool
    {
        // Validating all keys first.
        foreach ($keys as $key) {
            $this->validateKey($key);
        }

        $success = true;

        foreach ($keys as $key) {
            $success = $success && $this->deleteItem($key);
        }

        return $success;
    }

    /**
     * {@inheritdoc}
     */
    public function save(CacheItemInterface $item): bool
    {
        if (! $item instanceof CacheItem) {
            throw new InvalidArgumentException('$item must be an instance of '.CacheItem::class);
        }

        $expiresAt = $item->getExpiresAt();

        if (! $expiresAt) {
            try {
                $this->repository->forever($item->getKey(), $item->get());
            } catch (Throwable $exception) {
                return false;
            }

            return true;
        }

        $lifetime = static::computeLifetime($expiresAt);

        if ($lifetime <= 0) {
            $this->repository->forget($item->getKey());

            return false;
        }

        try {
            $this->repository->put($item->getKey(), $item->get(), $lifetime);
        } catch (Throwable $exception) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function saveDeferred(CacheItemInterface $item): bool
    {
        if (! $item instanceof CacheItem) {
            throw new InvalidArgumentException('$item must be an instance of '.CacheItem::class);
        }

        $expiresAt = $item->getExpiresAt();

        if ($expiresAt && ($expiresAt < new DateTimeImmutable())) {
            return false;
        }

        $item = (new CacheItem($item->getKey(), $item->get(), true))->expiresAt($expiresAt);

        $this->deferred[$item->getKey()] = $item;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function commit(): bool
    {
        $success = true;

        foreach ($this->deferred as $key => $item) {
            $success = $success && $this->save($item);
        }

        $this->deferred = [];

        return $success;
    }

    /**
     * @param  string  $key
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function validateKey(string $key): void
    {
        if (! is_string($key) || preg_match('#[{}\(\)/\\\\@:]#', $key)) {
            throw new InvalidArgumentException();
        }
    }

    protected static function computeLifetime(DateTimeInterface $expiresAt): int
    {
        $now = new DateTimeImmutable('now', $expiresAt->getTimezone());

        return $expiresAt->getTimestamp() - $now->getTimestamp();
    }

    public function __destruct()
    {
        $this->commit();
    }
}
