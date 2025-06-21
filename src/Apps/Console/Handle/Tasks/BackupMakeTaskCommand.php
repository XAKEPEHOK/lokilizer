<?php
/**
 * Created for lokilizer
 * Date: 2025-01-22 00:56
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Apps\Console\Handle\Tasks;

use Behat\Transliterator\Transliterator;
use DateTimeImmutable;
use DateTimeZone;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use XAKEPEHOK\Lokilizer\Apps\Console\Handle\HandleTaskCommand;
use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use XAKEPEHOK\Lokilizer\Components\ColorType;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Models\Glossary\Db\Storage\GlossaryRepo;
use XAKEPEHOK\Lokilizer\Models\Glossary\Glossary;
use XAKEPEHOK\Lokilizer\Models\LLM\Db\LLMEndpointRepo;
use XAKEPEHOK\Lokilizer\Models\LLM\LLMEndpoint;
use XAKEPEHOK\Lokilizer\Models\Localization\Db\RecordRepo;
use XAKEPEHOK\Lokilizer\Models\Localization\Record;
use XAKEPEHOK\Path\FileName;
use XAKEPEHOK\Path\Path;

class BackupMakeTaskCommand extends HandleTaskCommand
{

    public function __construct(
        private RecordRepo   $recordRepo,
        private GlossaryRepo $glossaryRepo,
        private LLMEndpointRepo $llmEndpointRepo,
        private Filesystem   $filesystem,
        ContainerInterface   $container,
    )
    {
        parent::__construct($container);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->getTaskData($input);

        $llmEndpoints = $this->llmEndpointRepo->findAll();
        $glossaries = $this->glossaryRepo->findAll();
        $recordsIds = $this->recordRepo->fetchIdsArray(true);

        $this->setMaxProgress(count($llmEndpoints) + count($glossaries) + count($recordsIds));

        $now = new DateTimeImmutable(
            'now',
            Current::hasUser() ? Current::getUser()->getTimezone() : new DateTimeZone('UTC')
        );

        $path = (new Path('downloads/'))
            ->down(Current::getProject()->id())
            ->down(substr($this->uuid, 0, 1))
            ->down($this->uuid);

        $directory = Path::root()->down('web')->down($path);

        $fileName = new FileName(Transliterator::urlize(Current::getProject()->getName()) . '-' . $now->format('YmdHis') . '.backup');
        $file = $directory->down($fileName);

        if (!$this->filesystem->exists($directory)) {
            $this->filesystem->mkdir($directory);
        }

        $backups = [];

        foreach ($llmEndpoints as $llm) {
            $data = $this->llmEndpointRepo->getMapper()->serialize($llm);
            $backups[LLMEndpoint::getModelAlias()][] = $data;
            $this->incCurrentProgress();
        }

        foreach ($glossaries as $glossary) {
            $data = $this->glossaryRepo->getMapper()->serialize($glossary);
            $backups[Glossary::getModelAlias()][] = $data;
            $this->incCurrentProgress();
        }

        foreach ($recordsIds as $recordsId) {
            $this->recordRepo->freeUpMemory();
            $record = $this->recordRepo->findById($recordsId);
            $data = $this->recordRepo->getMapper()->serialize($record);
            $backups[Record::getModelAlias()][] = $data;
            $this->incCurrentProgress();
        }

        $json = json_encode($backups, JSON_THROW_ON_ERROR);
        unset($backups);

        $salt = random_bytes(SODIUM_CRYPTO_PWHASH_SALTBYTES);
        $key = sodium_crypto_pwhash(
            SODIUM_CRYPTO_SECRETBOX_KEYBYTES,  // длина ключа
            $_ENV['SIGN_SECRET'],
            $salt,
            SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
            SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE
        );
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = sodium_crypto_secretbox($json, $nonce, $key);
        $encrypted = base64_encode($salt . $nonce . $ciphertext);

        $gz = gzopen($file,'w9');
        gzwrite($gz, $encrypted);
        gzclose($gz);

        $this->finishProgress(
            ColorType::Success,
            (new Path(RouteUri::home()))->down($path)->down($fileName)
        );

        return self::SUCCESS;
    }

    protected function getTimeLimit(): int
    {
        return 150;
    }

    protected static function name(): string
    {
        return 'backup-make';
    }
}