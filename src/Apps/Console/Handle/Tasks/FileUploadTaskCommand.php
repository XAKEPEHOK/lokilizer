<?php
/**
 * Created for lokilizer
 * Date: 2025-01-22 00:56
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Apps\Console\Handle\Tasks;

use Adbar\Dot;
use DateTimeImmutable;
use DiBify\DiBify\Manager\ModelManager;
use DiBify\DiBify\Manager\Transaction;
use JsonException;
use MongoDB\Driver\Exception\LogicException;
use PrinsFrank\Standards\Language\LanguageAlpha2;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Throwable;
use XAKEPEHOK\Lokilizer\Apps\Console\Handle\HandleTaskCommand;
use XAKEPEHOK\Lokilizer\Components\ColorType;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Models\Localization\Components\AbstractValue;
use XAKEPEHOK\Lokilizer\Models\Localization\Components\AbstractPluralValue;
use XAKEPEHOK\Lokilizer\Models\Localization\Components\SimpleValue;
use XAKEPEHOK\Lokilizer\Models\Localization\Db\RecordRepo;
use XAKEPEHOK\Lokilizer\Models\Localization\PluralRecord;
use XAKEPEHOK\Lokilizer\Models\Localization\SimpleRecord;
use XAKEPEHOK\Lokilizer\Models\Localization\Record;
use XAKEPEHOK\Path\Path;
use function Sentry\captureException;

class FileUploadTaskCommand extends HandleTaskCommand
{

    public function __construct(
        private Filesystem   $filesystem,
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
        $project = Current::getProject();
        $language = LanguageAlpha2::from($data['language']);
        $path = new Path($data['path']);

        $languages = $this->recordRepo->fetchLanguages(true, false);

        $isPrimaryLanguage = $language === $project->getPrimaryLanguage();

        try {
            $json = $this->filesystem->readFile($path);
        } catch (IOException) {
            $this->finishProgress(ColorType::Danger, 'Cannot read file');
            return self::SUCCESS;
        }

        try {
            $this->filesystem->remove(strval($path));
        } catch (Throwable $throwable) {
            captureException($throwable);
        }

        try {
            $translations = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $this->finishProgress(ColorType::Danger, 'Cannot parse json file');
            return self::SUCCESS;
        }

        try {
            $flat = (new Dot($translations))->flatten();
            $flat = $project->getFileFormatter()->factory()->parse($language, $flat);
            $this->setMaxProgress(count($flat));

            //Помечаем записи устаревшими только если обрабатывается главный язык
            if ($isPrimaryLanguage) {
                $outdatedRecords = array_filter(
                    $this->recordRepo->findAll(),
                    fn(Record $model) => !isset($flat[$model->getKey()])
                );
                $outdatedCount = count($outdatedRecords);
                $this->setMaxProgress(count($flat) + $outdatedCount);

                /** @var Record[][] $outdatedRecordChunks */
                $outdatedRecordChunks = array_chunk($outdatedRecords, 20);
                foreach ($outdatedRecordChunks as $outdatedRecordsChunk) {
                    $transaction = new Transaction($outdatedRecordsChunk);
                    foreach ($outdatedRecordsChunk as $outdatedRecord) {
                        $outdatedRecord->setOutdated(true);
                        $this->incCurrentProgress();
                    }
                    $this->modelManager->commit($transaction);
                }

                unset($outdatedRecords);
                unset($outdatedRecordChunks);
            }

            $addedCount = 0;
            $updatedCount = 0;
            $outdatedCount = $outdatedCount ?? 0;
            $skippedCount = 0;
            $errorsCount = 0;

            $position = 0;

            try {
                foreach ($flat as $flatKey => $value) {
                    $position++;

                    //Очищаем память в начале, чтобы внутри цикла смело делать continue
                    $this->modelManager->freeUpMemory();
                    $this->incCurrentProgress();

                    $record = $this->recordRepo->findByKey($flatKey);

                    if (!$isPrimaryLanguage && is_null($record)) {
                        $skippedCount++;
                        $this->addLogProgress(
                            $flatKey,
                            'Record with this key does not exists. Skipped, because language is not primary',
                            ColorType::Warning
                        );
                        continue;
                    }


                    $primary = $record?->getPrimaryValue();
                    //Если тип записи второстепенного языка не совпадает с типом основного, то пропускаем
                    if ($primary && !$isPrimaryLanguage && get_class($value) !== get_class($primary)) {
                        $message = $primary instanceof SimpleValue ? 'simple, not plural' : 'plural, not simple';
                        $this->addLogProgress($flatKey, "Value should be a {$message}. Skipped.", ColorType::Danger);
                        $errorsCount++;
                        $skippedCount++;
                        continue;
                    }


                    //Текущий язык, для которого загружается перевод
                    if (!$record) {
                        $record = $this->createModelByValue($flatKey, $value);
                        $hasChanges = true;
                        $addedCount++;
                    } else {
                        $hasChanges = $record->setValue($value);
                        $updatedCount = $updatedCount + intval($hasChanges);
                    }
                    $primary = $record->getPrimaryValue();

                    if ($isPrimaryLanguage) {
                        foreach ($languages as $lang) {
                            if (!$record->hasValue($lang)) {
                                $record->setValue($primary::getEmpty($lang));
                                $hasChanges = true;
                            }
                        }
                    }

                    $current = $record->getValue($language);
                    $primary = $record->getPrimaryValue();

                    $warnings = $current->validate($record);
                    if (!empty($warnings)) {
                        $errorsCount++;
                        $this->addLogProgress(
                            $flatKey,
                            $warnings,
                            ColorType::Danger
                        );
                    }

                    //Сохраняем позицию сортировки. Если это главный язык, то берем индекс. Иначе ставим позицию главного
                    $positionChanged = $record->setPosition($isPrimaryLanguage ? $position : $record->getPosition());

                    //Если мы загружаем переводы из файла для главного языка, значит они точно проверенные
                    if ($isPrimaryLanguage && !$current->verified && empty($warnings)) {
                        $current->verified = true;
                        $hasChanges = true;
                    }

                    //Помечаем что есть ошибки
                    if (!empty($warnings)) {
                        $current->setWarnings(count($warnings));
                        $hasChanges = true;
                    }

                    //Если мы восстанавливаем ключ для перевода, который раньше был outdated
                    if ($current === $primary && $record->getOutdatedAt() && !$hasChanges) {
                        $record->setOutdated(false);
                        $hasChanges = true;
                    }

                    //Если изменений нет, то идем дальше
                    if (!$hasChanges && !$positionChanged) {
                        continue;
                    }

                    $this->modelManager->commit(new Transaction([$record]));
                }
            } catch (Throwable $throwable) {
                $this->addLogProgress($flatKey, $throwable->getMessage(), ColorType::Danger);
                throw $throwable;
            }

            //Если мы загружаем не главный язык, то в нем могут отсутствовать строки из главного
            if (!$isPrimaryLanguage) {
                $existedKeys = $this->recordRepo->fetchKeysArray();
                $loosedKeys = array_diff($existedKeys, array_keys($flat));
                foreach ($loosedKeys as $flatKey) {
                    $this->modelManager->freeUpMemory();
                    $record = $this->recordRepo->findByKey($flatKey);

                    $value = $record->getValue($language) ?? $record->getPrimaryValue()::getEmpty($language);
                    $value->setWarnings(count($value->validate($record)));
                    $value->verified = false;
                    $this->modelManager->commit(new Transaction([$record]));
                    $this->addLogProgress($flatKey, 'Loosed key (exists in primary language, but loosed here)', ColorType::Warning);
                }
            }

            $this->addLogProgress('', '', ColorType::Nothing);
            $this->addLogProgress('Errors', $errorsCount, ColorType::Danger);
            $this->addLogProgress('Loosed', count($loosedKeys ?? []), ColorType::Warning);
            $this->addLogProgress('Skipped', $skippedCount, ColorType::Warning);
            $this->addLogProgress('Outdated', $outdatedCount, ColorType::Warning);
            $this->addLogProgress('Updated', $updatedCount, ColorType::Info);
            $this->addLogProgress('Added', $addedCount, ColorType::Success);

            $this->finishProgress(ColorType::Success, 'Successfully handled');

        } catch (Throwable $throwable) {
            $this->finishProgress(ColorType::Danger, $throwable->getMessage());
            throw $throwable;
        }

        return self::SUCCESS;
    }

    public function createModelByValue(string $flatKey, AbstractValue $value): Record
    {
        if ($value instanceof SimpleValue) {
            return new SimpleRecord($flatKey, $value);
        }

        if ($value instanceof AbstractPluralValue) {
            return new PluralRecord($flatKey, $value);
        }

        throw new LogicException('Invalid value type');
    }

    protected function getTimeLimit(): int
    {
        return 600;
    }

    protected static function name(): string
    {
        return 'file-upload';
    }
}