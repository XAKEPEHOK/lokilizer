<?php

namespace XAKEPEHOK\Lokilizer\Apps\Console\Handle;

use XAKEPEHOK\Lokilizer\Apps\Console\Components\MutexCommand;
use DiBify\DiBify\Id\UuidGenerator;
use Redis;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use XAKEPEHOK\Lokilizer\Components\ColorType;

class HandleQueueCommand extends MutexCommand
{

    public function __construct(
        private readonly Redis  $redis,
    )
    {
        parent::__construct('handle:queue');
        $this->addArgument('channel', InputArgument::OPTIONAL, 'Channel name', 'default');
        $this->addOption(
            'emulate',
            'e',
            InputOption::VALUE_NONE,
            'Emulate and show command, but without running',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('emulate')) {
            $output->writeln('-- Emulation mode --');
        }

        $output->writeln('');
        $channel = $input->getArgument('channel');
        return $this->withMutex(function () use ($input, $output, $channel) {
            $phpBinaryFinder = new PhpExecutableFinder();
            $phpBinaryPath = $phpBinaryFinder->find();
            while (true) {
                usleep(500000);
                
                $json = $this->redis->lPop("handle:queue:{$channel}");
                if ($json === false) {
                    continue;
                }

                $data = json_decode($json, true, JSON_THROW_ON_ERROR);
                list($timeout, $command, $task) = $data;

                $uuid = $task["___context___"]["uuid"] ?? UuidGenerator::generate();
                $this->redis->set(
                    "handle:tasks:{$channel}:$uuid",
                    json_encode($task),
                    ['ex' => 60 * 60 * 24]
                );

                $output->writeln('');
                $output->write("Run '{$command}' with id '{$uuid}': ");
                
                $process = new Process(['/usr/bin/timeout', "{$timeout}s", $phpBinaryPath, '/app/console.php', $command, $uuid]);
                $process->setTimeout($timeout);

                if ($input->getOption('emulate')) {
                    $output->writeln($process->getCommandLine());
                    $output->writeln($process->getCommandLine());
                    continue;
                }

                $process->start();

                while ($process->isRunning()) {
                    usleep(500000);
                    if ($this->redis->exists("progress:task:{$uuid}:stop")) {
                        $process->stop(0);
                        $this->redis->setex("progress:task:{$uuid}:finish", HandleTaskCommand::TTL, json_encode([
                            'message' => 'Force stopped by user',
                            'type' => ColorType::Warning->value,
                        ]));
                        $output->writeln('[Stopped by user]');
                    }
                }

                if ($process->isSuccessful()) {
                    $output->writeln('[OK]');
                } else {
                    $output->writeln($process->getOutput());
                    $output->writeln($process->getErrorOutput());
                }
            }
        }, $this->getName() . '_' . $channel);
    }

}