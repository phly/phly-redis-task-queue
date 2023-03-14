<?php

declare(strict_types=1);

namespace Phly\RedisTaskQueue\Command;

use Phly\RedisTaskQueue\RedisTaskQueue;
use Psr\EventDispatcher\EventDispatcherInterface;
use React\EventLoop\LoopInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function sprintf;

final class TaskRunner extends Command implements AllowedSignals
{
    use LoopSignalsTrait;

    public function __construct(
        private RedisTaskQueue $queue,
        private EventDispatcherInterface $dispatcher,
        private LoopInterface $loop,
        private readonly float $interval = 1.0,
        /** @psalm-var list<int> */
        private readonly array $signalsToRegister = self::DEFAULT_SIGNAL_LIST,
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
        $this->registerTerminationSignals($this->signalsToRegister, $this->loop, $output);
        $this->registerTaskHandler($this->loop, $output);
        $output->writeln('<info>Starting task runnner</info>');
        $this->loop->run();
        $output->writeln('<info>Task runner stopped</info>');

        return Command::SUCCESS;
    }

    private function registerTaskHandler(LoopInterface $loop, OutputInterface $output): void
    {
        $loop->addPeriodicTimer($this->interval, function () use ($output): void {
            try {
                $task = $this->queue->retrieveNextTask();
                if (null === $task) {
                    return;
                }

                $this->dispatcher->dispatch($task);
            } catch (Throwable $e) {
                $output->writeln(sprintf('<error>Error processing queue: %s</error>', $e->getMessage()));
            }
        });
    }
}
