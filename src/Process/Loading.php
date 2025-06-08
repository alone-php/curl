<?php

namespace AlonePhp\Curl\Process;

use Closure;

/**
 * 回调处理
 */
class Loading {

    private static array|null $progressCallback = [];

    /**
     * @param string|int $uuid
     * @param mixed      $callback
     * @return array|null
     */
    public static function setProgress(string|int $uuid, mixed $callback): array|null {
        if (is_callable($callback) && $callback instanceof Closure) {
            $uuid = !empty($uuid) ? $uuid : 0;
            static::$progressCallback[$uuid] = $callback;
            return [static::class, "callProgress_" . $uuid];
        }
        return null;
    }

    public static function __callStatic(string $name, array $arguments): int {
        $uuid = substr($name, strlen('callProgress_'));
        if (strlen($uuid) > 0 && !empty($callback = (static::$progressCallback[$uuid] ?? ''))) {
            $callProgress = function($callback, $resource, $download_size, $downloaded, $upload_size, $uploaded): void {
                if ($download_size > 0) {
                    $progress = round($downloaded / $download_size * 100, 2);
                    $callback($progress, $download_size, $resource, $downloaded, $upload_size, $uploaded);
                }
            };
            call_user_func($callProgress, $callback, ...$arguments);
        }
        return 0;
    }
}