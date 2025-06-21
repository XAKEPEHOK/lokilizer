<?php
/**
 * Created for lokilizer
 * Date: 2025-02-16 23:15
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Apps\Console\Handle\Tasks;

use DiBify\DiBify\Manager\ModelManager;
use DiBify\DiBify\Manager\Transaction;
use PrinsFrank\Standards\Language\LanguageAlpha2;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use XAKEPEHOK\Lokilizer\Apps\Console\Handle\HandleTaskCommand;
use XAKEPEHOK\Lokilizer\Components\ColorType;
use XAKEPEHOK\Lokilizer\Models\Localization\Db\RecordRepo;
use XAKEPEHOK\Lokilizer\Models\Localization\Record;

class BatchDeleteTaskCommand extends HandleTaskCommand
{

    public function __construct(
        private ModelManager $modelManager,
        private RecordRepo   $recordRepo,
        ContainerInterface   $container,
    )
    {
        parent::__construct($container);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $data = $this->getTaskData($input);

        $includeActual = $data['includeActual'];
        $includeOutdated = $data['includeOutdated'];

        $all = $this->recordRepo->fetchIdsArray(true);

        $actual = $this->recordRepo->fetchIdsArray(false);
        $outdated = array_diff($all, $actual);

        $count = 0;

        if ($includeActual) {
            $count+= count($actual);
        }

        if ($includeOutdated) {
            $count+= count($outdated);
        }

        $this->setMaxProgress($count);

        if ($includeOutdated) {
            $chunks = array_chunk($outdated, 20);
            foreach ($chunks as $chunk) {
                $this->modelManager->freeUpMemory();
                $records = $this->recordRepo->findByIds($chunk);
                $this->modelManager->commit(new Transaction([], $records));
                $this->incCurrentProgress(count($chunk));
            }
            $this->addLogProgress('Outdated deleted', count($outdated), ColorType::Info);
        }

        if ($includeActual) {
            $chunks = array_chunk($actual, 20);
            foreach ($chunks as $chunk) {
                $this->modelManager->freeUpMemory();
                $records = $this->recordRepo->findByIds($chunk);
                $this->modelManager->commit(new Transaction([], $records));
                $this->incCurrentProgress(count($chunk));
            }
            $this->addLogProgress('Actual deleted', count($actual), ColorType::Info);
        }

        $this->finishProgress(ColorType::Success, 'Successfully deleted');
        return self::SUCCESS;
    }

    protected function getTimeLimit(): int
    {
        return 600;
    }

    protected static function name(): string
    {
        return 'batchDelete';
    }
}