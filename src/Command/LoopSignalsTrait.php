<?php

declare(strict_types=1);

namespace Phly\RedisTaskQueue\Command;

use React\EventLoop\LoopInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function in_array;
use function sprintf;

use const SIGINT;
use const SIGKILL;
use const SIGTERM;

trait LoopSignalsTrait
{
    /** @var array<int, string> */
    // phpcs:ignore WebimpressCodingStandard.NamingConventions.ValidVariableName.NotCamelCapsProperty
    private array $signals = [
        SIGTERM => "Caught TERM signal",
        SIGKILL => "Caught KILL signal",
        SIGINT  => "Caught INT signal",
    ];

    /** @psalm-param list<int> $signals */
    private function registerTerminationSignals(array $signals, LoopInterface $loop, OutputInterface $output): void
    {
        foreach ($this->signals as $signal => $message) {
            if (! in_array($signal, $signals)) {
                continue;
            }

            $loop->addSignal($signal, function () use ($loop, $message, $output): void {
                $output->writeln(sprintf('<info>%s</info>', $message));
                $loop->stop();
            });
        }
    }
}
