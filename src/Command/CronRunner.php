<?php

declare(strict_types=1);

namespace Phly\RedisTaskQueue\Command;

use Phly\RedisTaskQueue\Cron\Dispatcher;
use React\EventLoop\LoopInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class CronRunner extends Command
{
    use LoopSignalsTrait;

    public function __construct(
        private Dispatcher $dispatcher,
        private LoopInterface $loop,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Run crontab');
        // phpcs:ignore Generic.Files.LineLength.TooLong
        $this->setHelp('Creates a foreground process that manages scheduling of crontab jobs as provided in application configuration');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->registerTerminationSignals($this->loop, $output);
        $this->registerCronHandler($output);

        $output->writeln('<info>Starting cron runner</info>');
        $this->loop->run();
        $output->writeln('<info>Cron runner stopped</info>');

        return Command::SUCCESS;
    }

    private function registerCronHandler(OutputInterface $output): void
    {
        $this->loop->addPeriodicTimer(60, function () use ($output): void {
            ($this->dispatcher)($output);
        });
    }
}
