<?php

namespace LightLogger\Tools\Log;

use LightLogger\Tools\Env;

class Log
{
    public static LogLevel $log_level = LogLevel::All;

    private static function getConsoleColor(LogLevel $level): ANSIColor
    {
        return match ($level) {
            LogLevel::Warning => ANSIColor::Orange,
            LogLevel::Error => ANSIColor::Red,
            LogLevel::Success => ANSIColor::Green,
            LogLevel::Blue => ANSIColor::Blue,
            LogLevel::Cyan => ANSIColor::Cyan,
            LogLevel::Magenta => ANSIColor::Magenta,
            default => ANSIColor::Default,
        };
    }

    public static function getLogWithColor(string $message, LogLevel $level = null, ANSIColor $color = null): string
    {
        $colorCode = $color?->getCode() ?? ($level ? self::getConsoleColor($level)->getCode() : ANSIColor::Default->getCode());
        $colorEnd = ANSIColor::reset();

        $timestamp = !Env::isProduction() ? date("H:i:s") . ' : ' : '';
        return $colorCode . $timestamp . $message . $colorEnd . PHP_EOL;
    }

    private static function logWithColor(string $message, LogLevel $level): void
    {
        if (self::$log_level === LogLevel::All || self::$log_level === $level) {
            echo self::getLogWithColor($message, $level);
        }
    }

    public static function info(string $message): void
    {
        self::logWithColor($message, LogLevel::Info);
    }

    public static function warning(string $message): void
    {
        self::logWithColor($message, LogLevel::Warning);
    }

    public static function error(string $message): void
    {
        self::logWithColor($message, LogLevel::Error);
    }

    public static function success(string $message): void
    {
        self::logWithColor($message, LogLevel::Success);
    }

    public static function custom(string $message, ANSIColor $color): void
    {
        echo self::getLogWithColor($message, color: $color);
    }

    public static function auto(string $message, LogLevel $level = LogLevel::Info): void
    {
        switch ($level) {
            case LogLevel::Error:
                self::error($message);
                break;
            case LogLevel::Success:
                self::success($message);
                break;
            case LogLevel::Warning:
                self::warning($message);
                break;
            default:
                self::info($message);
                break;
        }
    }
}
