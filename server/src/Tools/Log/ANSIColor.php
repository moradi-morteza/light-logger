<?php

namespace LightLogger\Tools\Log;

enum ANSIColor: string
{
    // Text Colors
    case Black = '0;30';
    case Red = '0;31';
    case Green = '0;32';
    case Yellow = '0;33';
    case Blue = '0;34';
    case Magenta = '0;35';
    case Cyan = '0;36';
    case Default = '0;37';
    case Orange = '38;5;214';

    // Bold/Bright Text Colors
    case BrightBlack = '1;30';  // Gray
    case BrightRed = '1;31';
    case BrightGreen = '1;32';
    case BrightYellow = '1;33';
    case BrightBlue = '1;34';
    case BrightMagenta = '1;35';
    case BrightCyan = '1;36';
    case BrightWhite = '1;37';

    // Background Colors
    case BackgroundBlack = '40';
    case BackgroundRed = '41';
    case BackgroundGreen = '42';
    case BackgroundYellow = '43';
    case BackgroundBlue = '44';
    case BackgroundMagenta = '45';
    case BackgroundCyan = '46';
    case BackgroundWhite = '47';

    // Bold/Bright Background Colors
    case BackgroundBrightBlack = '100'; // Gray
    case BackgroundBrightRed = '101';
    case BackgroundBrightGreen = '102';
    case BackgroundBrightYellow = '103';
    case BackgroundBrightBlue = '104';
    case BackgroundBrightMagenta = '105';
    case BackgroundBrightCyan = '106';
    case BackgroundBrightWhite = '107';

    // Reset
    case Reset = '0';

    // Get the full escape code for the color
    public function getCode(): string
    {
        return "\033[" . $this->value . "m";
    }

    // Static method to reset the color
    public static function reset(): string
    {
        return "\033[0m";
    }
}
