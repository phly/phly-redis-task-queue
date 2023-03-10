<?php

declare(strict_types=1);

namespace Phly\RedisTaskQueue;

use JsonException;

use function is_array;
use function json_decode;
use function json_encode;

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/** @throws JsonException */
function jsonDecode(string $value): array
{
    $deserialized = json_decode($value, associative: true, flags: JSON_THROW_ON_ERROR);

    if (! is_array($deserialized)) {
        throw new JsonException('JSON deserialization did not return array');
    }

    return $deserialized;
}

function jsonEncode(mixed $value): string
{
    return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}
