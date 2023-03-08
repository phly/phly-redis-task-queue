<?php

declare(strict_types=1);

namespace Phly\RedisTaskQueue\Command;

use Phly\RedisTaskQueue\RedisTaskQueue;
use Phly\RedisTaskQueue\Worker;
use React\EventLoop\LoopInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function sprintf;

use const SIGINT;
use const SIGKILL;
use const SIGTERM;

final class TaskRunner extends Command
{
    private const SIGNALS = [
        SIGTERM => "Caught TERM signal",
        SIGKILL => "Caught KILL signal",
        SIGINT  => "Caught INT signal",
    ];

    public function __construct(
        private RedisTaskQueue $queue,
        private Worker $worker,
        private LoopInterface $loop,
        private readonly float $interval = 1.0,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Start the task worker');
        $this->setHelp('Run the Redis task queue worker');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->registerTerminationSignals($output);
        $this->registerTaskHandler($output);
        $output->writeln('<info>Starting task runnner</info>');
        $this->loop->run();
        $output->writeln('<info>Task runner stopped</info>');

        return Command::SUCCESS;
    }

    private function registerTerminationSignals(OutputInterface $output): void
    {
        foreach (self::SIGNALS as $signal => $message) {
            $this->loop->addSignal($signal, function () use ($message, $output): void {
                $output->writeln(sprintf('<info>%s</info>', $message));
                $this->loop->stop();
            });
        }
    }

    private function registerTaskHandler(OutputInterface $output): void
    {
        $this->loop->addPeriodicTimer($this->interval, function () use ($output): void {
            try {
                if (! $this->queue->hasPendingTasks()) {
                    return;
                }

                $this->worker->process($this->queue->retrieveNextTask());
            } catch (Throwable $e) {
                $output->writeln(sprintf('<error>Error processing queue: %s</error>', $e->getMessage()));
            }
        });
    }
}
