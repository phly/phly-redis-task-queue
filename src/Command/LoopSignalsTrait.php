<?php

declare(strict_types=1);

namespace Phly\RedisTaskQueue\Command;

use React\EventLoop\LoopInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function sprintf;

use const SIGINT;
use const SIGKILL;
use const SIGTERM;

trait LoopSignalsTrait
{
    /** @var array<int, string> */
    // phpcs:ignore WebimpressCodingStandard.NamingConventions.ValidVariableName.NotCamelCapsProperty
    private static array $SIGNALS = [
        SIGTERM => "Caught TERM signal",
        SIGKILL => "Caught KILL signal",
        SIGINT  => "Caught INT signal",
    ];

    private function registerTerminationSignals(LoopInterface $loop, OutputInterface $output): void
    {
        foreach (self::$SIGNALS as $signal => $message) {
            $loop->addSignal($signal, function () use ($loop, $message, $output): void {
                $output->writeln(sprintf('<info>%s</info>', $message));
                $loop->stop();
            });
        }
    }
}
