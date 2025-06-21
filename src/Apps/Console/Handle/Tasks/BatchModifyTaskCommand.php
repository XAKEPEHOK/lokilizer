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
use XAKEPEHOK\Lokilizer\Models\Localization\Components\CardinalPluralValue;
use XAKEPEHOK\Lokilizer\Models\Localization\Components\OrdinalPluralValue;
use XAKEPEHOK\Lokilizer\Models\Localization\Components\SimpleValue;
use XAKEPEHOK\Lokilizer\Models\Localization\Db\RecordRepo;
use XAKEPEHOK\Lokilizer\Models\Localization\Record;

class BatchModifyTaskCommand extends HandleTaskCommand
{

    use RecordFilterTrait;

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

        $language = LanguageAlpha2::from($data['language']);
        $keyContains = $data['keyContains'];
        $valueContains = $data['valueContains'];
        $includeOutdated = $data['includeOutdated'];

        $this->addLogProgress('Include outdated', $includeOutdated ? 'yes' : 'no', ColorType::Nothing);
        $this->addLogProgress('Value contains', $valueContains, ColorType::Nothing);
        $this->addLogProgress('Key contains', $keyContains, ColorType::Nothing);
        $this->addLogProgress('Language', $language->value, ColorType::Nothing);

        $languages = $this->recordRepo->fetchLanguages(true, false);

        $recordsIds = $this->recordRepo->fetchIdsArray($includeOutdated);
        $this->setMaxProgress(count($recordsIds));

        foreach ($recordsIds as $id) {
            $this->modelManager->freeUpMemory();

            /** @var Record $record */
            $record = $this->recordRepo->findById($id);

            if ($this->shouldSkipRecord($record, $language, $keyContains, $valueContains)) {
                $this->incCurrentProgress();
                continue;
            }

            foreach ($languages as $lang) {
                $value = $record->getValue($lang);
                if (!$value) {
                    $empty = $record->getPrimaryValue()::getEmpty($lang);
                    $empty->setWarnings($empty->validate($record));
                    $record->setValue($empty);
                }
            }

            $current = $record->getValue($language);

            if ($data['removeComment']) {
                $record->setComment('');
            }

            if ($data['trim']) {

                if ($current instanceof SimpleValue) {
                    $current = new SimpleValue($current->getLanguage(), trim($current));
                }

                if ($current instanceof CardinalPluralValue) {
                    $current = new CardinalPluralValue(
                        $current->getLanguage(),
                        trim($current->getCategoryValue('zero')),
                        trim($current->getCategoryValue('one')),
                        trim($current->getCategoryValue('two')),
                        trim($current->getCategoryValue('few')),
                        trim($current->getCategoryValue('many')),
                        trim($current->getCategoryValue('other')),
                    );
                }

                if ($current instanceof OrdinalPluralValue) {
                    $current = new OrdinalPluralValue(
                        $current->getLanguage(),
                        trim($current->getCategoryValue('zero')),
                        trim($current->getCategoryValue('one')),
                        trim($current->getCategoryValue('two')),
                        trim($current->getCategoryValue('few')),
                        trim($current->getCategoryValue('many')),
                        trim($current->getCategoryValue('other')),
                    );
                }

                $record->setValue($current);
                $current = $record->getValue($language);
            }

            if ($data['removeValue']) {
                $current = $current::getEmpty($language);
                $record->setValue($current);
            }

            if ($data['revalidate'] || $data['removeValue']) {
                $warnings = $current->validate($record);
                $current->setWarnings(count($warnings));
            }

            if ($data['removeVerification']) {
                $current->verified = false;
            }

            if ($data['removeSuggested']) {
                $current->setSuggested(null);
            }

            $this->modelManager->commit(new Transaction([$record]));
            $this->incCurrentProgress();
        }

        $this->finishProgress(ColorType::Success, 'Successfully handled for ' . $language->name);

        return self::SUCCESS;
    }

    protected function getTimeLimit(): int
    {
        return 600;
    }

    protected static function name(): string
    {
        return 'batchModify';
    }
}