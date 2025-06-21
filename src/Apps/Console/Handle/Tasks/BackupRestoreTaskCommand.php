<?php
/**
 * Created for lokilizer
 * Date: 2025-01-22 00:56
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Apps\Console\Handle\Tasks;

use DiBify\DiBify\Manager\ModelManager;
use DiBify\DiBify\Manager\Transaction;
use DiBify\DiBify\Repository\Storage\StorageData;
use JsonException;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use XAKEPEHOK\Lokilizer\Apps\Console\Handle\HandleTaskCommand;
use XAKEPEHOK\Lokilizer\Components\ColorType;
use XAKEPEHOK\Lokilizer\Models\Glossary\Db\Storage\GlossaryRepo;
use XAKEPEHOK\Lokilizer\Models\Glossary\Glossary;
use XAKEPEHOK\Lokilizer\Models\LLM\Db\LLMEndpointRepo;
use XAKEPEHOK\Lokilizer\Models\LLM\LLMEndpoint;
use XAKEPEHOK\Lokilizer\Models\Localization\Db\RecordRepo;
use XAKEPEHOK\Lokilizer\Models\Localization\Record;
use XAKEPEHOK\Path\Path;

class BackupRestoreTaskCommand extends HandleTaskCommand
{

    public function __construct(
        private ModelManager $modelManager,
        private LLMEndpointRepo $llmEndpointRepo,
        private GlossaryRepo $glossaryRepo,
        private RecordRepo   $recordRepo,
        ContainerInterface   $container,
    )
    {
        parent::__construct($container);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $data = $this->getTaskData($input);
        $path = new Path($data['path']);
        $restoreLlm = $data['llm'];
        $restoreGlossary = $data['glossary'];
        $restoreRecords = $data['records'];

        $gz = gzopen($path, 'r');
        if (!$gz) {
            $this->finishProgress(ColorType::Danger, 'Cannot read file');
            return self::SUCCESS;
        }

        $rawData = '';
        while (!gzeof($gz)) {
            $rawData .= gzread($gz, 4096);
        }
        gzclose($gz);

        $data = base64_decode($rawData);
        $salt = substr($data, 0, SODIUM_CRYPTO_PWHASH_SALTBYTES);
        $nonce = substr($data, SODIUM_CRYPTO_PWHASH_SALTBYTES, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = substr($data, SODIUM_CRYPTO_PWHASH_SALTBYTES + SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $key = sodium_crypto_pwhash(
            SODIUM_CRYPTO_SECRETBOX_KEYBYTES,
            $_ENV['SIGN_SECRET'],
            $salt,
            SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
            SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE
        );
        $decrypted = sodium_crypto_secretbox_open($ciphertext, $nonce, $key);

        if ($decrypted === false) {
            $this->finishProgress(ColorType::Danger, 'Cannot decrypt file');
            return self::SUCCESS;
        }

        try {
            $data = json_decode($decrypted, true, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $this->finishProgress(ColorType::Danger, 'Cannot parse decrypted json data');
            return self::SUCCESS;
        }

        $llmCount = $restoreLlm ? count($data[LLMEndpoint::getModelAlias()]) : 0;
        $glossariesCount = $restoreGlossary ? count($data[Glossary::getModelAlias()]) : 0;
        $recordsCount = $restoreRecords ? count($data[Record::getModelAlias()]) : 0;
        $this->setMaxProgress($llmCount + $glossariesCount + $recordsCount);

        if ($restoreLlm) {
            $glossaries = $this->llmEndpointRepo->findAll();
            $this->modelManager->commit(new Transaction([], $glossaries));
            $this->llmEndpointRepo->freeUpMemory();

            foreach ($data[LLMEndpoint::getModelAlias()] as $glossaryData) {
                $glossary = $this->llmEndpointRepo->getMapper()->deserialize(
                    StorageData::fromArray($glossaryData),
                );
                $this->modelManager->commit(new Transaction([$glossary]));
                $this->llmEndpointRepo->freeUpMemory();
                $this->incCurrentProgress();
            }
        }

        if ($restoreGlossary) {
            $glossaries = $this->glossaryRepo->findAll();
            $this->modelManager->commit(new Transaction([], $glossaries));
            $this->glossaryRepo->freeUpMemory();

            foreach ($data[Glossary::getModelAlias()] as $glossaryData) {
                $glossary = $this->glossaryRepo->getMapper()->deserialize(
                    StorageData::fromArray($glossaryData),
                );
                $this->modelManager->commit(new Transaction([$glossary]));
                $this->glossaryRepo->freeUpMemory();
                $this->incCurrentProgress();
            }
        }

        if ($restoreRecords) {
            $recordIds = $this->recordRepo->fetchIdsArray(true);
            $chunkedRecordIds = array_chunk($recordIds, 100);
            foreach ($chunkedRecordIds as $recordIdsChunk) {
                $records = $this->recordRepo->findByIds($recordIdsChunk);
                $this->modelManager->commit(new Transaction([], $records));
                $this->recordRepo->freeUpMemory();
            }

            foreach ($data[Record::getModelAlias()] as $recordData) {
                $record = $this->recordRepo->getMapper()->deserialize(
                    StorageData::fromArray($recordData),
                );
                $this->modelManager->commit(new Transaction([$record]));
                $this->recordRepo->freeUpMemory();
                $this->incCurrentProgress();
            }
        }

        $this->finishProgress(ColorType::Success, 'Backup restored');

        return self::SUCCESS;
    }

    protected function getTimeLimit(): int
    {
        return 300;
    }

    protected static function name(): string
    {
        return 'backup-restore';
    }
}