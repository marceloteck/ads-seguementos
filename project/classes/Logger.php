<?php

declare(strict_types=1);

class Logger
{
    private string $logFile;

    public function __construct(string $logFile)
    {
        $this->logFile = $logFile;
    }

    public function error(string $message, array $context = []): void
    {
        $this->write('ERROR', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->write('INFO', $message, $context);
    }

    private function write(string $level, string $message, array $context): void
    {
        $date = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DateTimeInterface::ATOM);
        $line = sprintf(
            "[%s] [%s] %s %s\n",
            $date,
            $level,
            $message,
            !empty($context) ? json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : ''
        );

        $dir = dirname($this->logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        file_put_contents($this->logFile, $line, FILE_APPEND | LOCK_EX);
    }
}
