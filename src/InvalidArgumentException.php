<?php

declare(strict_types=1);

namespace EinarHansen\Cache;

use Exception;
use Psr\Cache\InvalidArgumentException as InvalidArgumentExceptionInterface;

final class InvalidArgumentException extends Exception implements InvalidArgumentExceptionInterface
{
}
