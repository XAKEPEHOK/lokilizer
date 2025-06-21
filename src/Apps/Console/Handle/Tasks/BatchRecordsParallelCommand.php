<?php
/**
 * Created for lokilizer
 * Date: 2025-03-02 22:08
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Apps\Console\Handle\Tasks;

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use XAKEPEHOK\Lokilizer\Apps\Console\Handle\HandleTaskCommand;
use XAKEPEHOK\Lokilizer\Components\ColorType;
use XAKEPEHOK\Lokilizer\Models\Localization\Db\RecordRepo;
use XAKEPEHOK\Lokilizer\Models\Localization\Record;

abstract class BatchRecordsParallelCommand extends HandleTaskCommand
{

    protected int $llmTimeout = 60;

    public function __construct(
        protected RecordRepo $recordRepo,
        ContainerInterface $container
    )
    {
        parent::__construct($container);
        $this->addOption('recordId', null, InputOption::VALUE_REQUIRED);
    }

    protected function handleParallelProcesses(InputInterface $input, array $recordsIds): void
    {
        $phpBinaryFinder = new PhpExecutableFinder();
        $phpBinaryPath = $phpBinaryFinder->find();

        /** @var Process[] $processes */
        $processes = [];

        /** @var Record[] $records */
        $records = [];

        $handleProcesses = function () use (&$processes, &$records) {
            foreach ($processes as $processedIdentity => $process) {
                $record = $records[$processedIdentity];
                if ($process->isTerminated()) {
                    switch ($process->getExitCode()) {
                        case self::FAILURE:
                        case self::INVALID:
                            $this->addLogProgress(
                                $record->getKey(),
                                'Failed to start subprocess: ' . $process->getErrorOutput() . ' | ' . $process->getOutput(),
                                ColorType::Danger
                            );
                            $this->incCustomCounter('errors');
                            break;
                    }

                    $process->stop(0, SIGKILL);
                    unset($processes[$processedIdentity]);
                    unset($records[$processedIdentity]);
                    $this->incCurrentProgress();
                } else {
                    try {
                        $process->checkTimeout();
                    } catch (ProcessTimedOutException) {
                        $this->addLogProgress($record->getKey(), 'Time limit of ' . $this->llmTimeout . ' sec', ColorType::Danger);
                        $this->incCustomCounter('errors');
                        unset($processes[$processedIdentity]);
                        unset($records[$processedIdentity]);
                        $this->incCurrentProgress();
                        $process->stop(0, SIGKILL);
                    }
                }
            }
        };

        foreach ($recordsIds as $id) {
            $this->recordRepo->freeUpMemory();

            while (count($processes) >= 10) {
                $handleProcesses();
                usleep(500000);
            }

            /** @var Record $record */
            $record = $this->recordRepo->findById($id);

            if ($this->earlySkip($record)) {
                $this->incCurrentProgress();
                continue;
            }

            $records[$id] = $record;

            $process = new Process([$phpBinaryPath, '/app/console.php', $this->getName(), $input->getArgument('task'), "--recordId={$id}"]);
            $process->setTimeout($this->llmTimeout);
            $processes[$id] = $process;
            $process->start();
        }

        while (count($processes) > 0) {
            $handleProcesses();
            sleep(1);
        }
    }

    abstract protected function handleOne(string $id): void;

    protected function earlySkip(Record $record): bool
    {
        return false;
    }

}