<?php

namespace App\Components\Utility;

use Psr\Http\Message\StreamInterface;

class JsonStreamWrapper
{
    /**
     * PHP automatically assigns the stream context here.
     * Must be public for PHP’s stream wrapper API.
     */
    public $context;

    /**
     * Stream attributes
     */
    private static ?StreamInterface $source = null;
    private static bool $started = false;
    private static bool $ended = false;
    private static string $wrapper;

    public static function setSource(StreamInterface $stream, ?string $wrapper = null): void
    {
        if (empty($wrapper)) {
            $wrapper = 'JsonStream' . spl_object_id($stream);
        }
        if (!in_array($wrapper, stream_get_wrappers())) {
            stream_wrapper_register($wrapper, self::class);
        }
        self::$wrapper = $wrapper;
        self::$source = $stream;
        self::$started = false;
        self::$ended = false;
    }

    public static function getWrapper()
    {
        return self::$wrapper;
    }

    public function stream_open($path, $mode, $options, &$opened_path)
    {
        return true;
    }

    public function stream_read($count): string
    {
        if (self::$ended || self::$source === null || self::$source->eof()) return '';

        $chunk = self::$source->read($count) ?? '';

        if (!self::$started) {
            $pos = strpos($chunk, '{');
            if ($pos !== false) {
                self::$started = true;
                $chunk = substr($chunk, $pos);
            } else {
                return $this->stream_read($count);
            }
        }

        $endPos = strpos($chunk, ');');
        if ($endPos !== false) {
            $chunk = substr($chunk, 0, $endPos);
            self::$ended = true;
        }

        return $chunk;
    }

    public function stream_eof(): bool
    {
        return self::$ended || self::$source === null || self::$source->eof();
    }
}
