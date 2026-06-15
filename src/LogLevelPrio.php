<?php

declare(strict_types=1);

namespace PsrLogger;

use Psr\Log\LogLevel;

class LogLevelPrio
{
    const LEVELS = [
        LogLevel::EMERGENCY => 800,
        LogLevel::ALERT => 700,
        LogLevel::CRITICAL => 600,
        LogLevel::ERROR => 500,
        LogLevel::WARNING => 400,
        LogLevel::NOTICE => 300,
        LogLevel::INFO => 200,
        LogLevel::DEBUG => 100,
    ];
}
