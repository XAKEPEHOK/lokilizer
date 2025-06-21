<?php

namespace XAKEPEHOK\Lokilizer\Apps\Console\Handle;

use DateTimeImmutable;
use DateTimeZone;
use DiBify\DiBify\Id\UuidGenerator;
use DiBify\DiBify\Model\Reference;
use XAKEPEHOK\Lokilizer\Components\ColorType;
use XAKEPEHOK\Lokilizer\Components\Current;
use Psr\Container\ContainerInterface;
use Redis;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use XAKEPEHOK\Lokilizer\Models\Project\Project;
use XAKEPEHOK\Lokilizer\Models\User\User;

abstract class HandleTaskCommand extends Command
{

    public const TTL = 60 * 60 * 24;

    private Redis $redis;

    protected ?string $uuid = null;

    public function __construct(
        protected readonly ContainerInterface $container
    )
    {
        $this->redis = $this->container->get(Redis::class);
        parent::__construct('handle:task:' . static::name());
        $this->addArgument('task', InputArgument::REQUIRED);
    }

    public function publish(array $data): string
    {
        $uuid = UuidGenerator::generate();
        $currentContext = [
            'uuid' => $uuid,
        ];

        if ($this->useCurrentContext()) {
            $currentContext['performer'] = Reference::to(Current::getUser());
            $currentContext['project'] = Reference::to(Current::getProject());
        }

        $this->redis->rPush(
            "handle:queue:{$this->channel()}",
            json_encode([$this->getTimeLimit(), $this->getName(), [
                ...$data,
                '___context___' => $currentContext,
            ]])
        );
        return $uuid;
    }

    protected function getTaskData(InputInterface $input, bool $isPrimaryTask = true): array
    {
        $task = $input->getArgument('task');
        $json = $this->redis->get("handle:tasks:{$this->channel()}:{$task}");
        if ($json === false) {
            throw new RuntimeException('Invalid task data');
        }

        $data = json_decode($json, true, JSON_THROW_ON_ERROR);
        $title = $data['title'] ?? '';
        $this->redis->setex("progress:task:{$this->uuid}:title", self::TTL, $title);
        $this->uuid = $data['___context___']['uuid'];

        if ($this->useCurrentContext()) {
            /** @var User $performer */
            $performer = Reference::fromArray($data['___context___']['performer'])->getModel();
            Current::setUser($performer);

            /** @var Project $project */
            $project = Reference::fromArray($data['___context___']['project'])->getModel();
            Current::setProject($project);

            if ($isPrimaryTask && !empty($title)) {
                $key = "batch:latest:{$project->id()}";
                $this->redis->lpush(
                    $key,
                    json_encode([
                        'title' => $title . ' at ' . (new DateTimeImmutable())->format('Y-m-d H:i'),
                        'uuid' => $this->uuid
                    ])
                );
                $this->redis->ltrim($key, 0, 4);
                $this->redis->expire($key, static::TTL);
            }

            if ($isPrimaryTask) {
                $this->addLogProgress('Performer', strval($performer->getName()), ColorType::Nothing);
            }
        }

        if ($isPrimaryTask) {
            $timezone = Current::hasUser() ? Current::getUser()->getTimezone() : new DateTimeZone('UTC');
            $now = new DateTimeImmutable('now', $timezone);
            $this->addLogProgress('Started at', $now->format('Y-m-d H:i:s T'), ColorType::Nothing);

            $this->redis->setex("progress:task:{$this->uuid}:startedAt", self::TTL, time());
        }

        return $data;
    }

    protected function setMaxProgress(int $maxProgress): void
    {
        $this->redis->setex("progress:task:{$this->uuid}:max", self::TTL, $maxProgress);
    }

    protected function setCurrentProgress(int $maxProgress): void
    {
        $this->redis->setex("progress:task:{$this->uuid}:current", self::TTL, $maxProgress);
        $this->redis->expire("progress:task:{$this->uuid}:max", self::TTL);
    }

    protected function incCurrentProgress(int $by = 1): void
    {
        $this->redis->incr("progress:task:{$this->uuid}:current", $by);
        $this->redis->expire("progress:task:{$this->uuid}:current", self::TTL);
        $this->redis->expire("progress:task:{$this->uuid}:max", self::TTL);
    }

    protected function getCustomCounter(string $counter): int
    {
        return intval($this->redis->hGet("progress:task:{$this->uuid}:customCounter", $counter));
    }

    protected function incCustomCounter(string $counter, int $increment = 1): void
    {
        $this->redis->hIncrBy("progress:task:{$this->uuid}:customCounter", $counter, $increment);
        $this->redis->expire("progress:task:{$this->uuid}:customCounter", self::TTL);
    }

    protected function addLogProgress(string $key, string|array $message, ColorType $type): void
    {
        $this->createBaseKeys();
        $this->redis->lPush("progress:task:{$this->uuid}:logs", json_encode([
            'time' => time(),
            'key' => $key,
            'message' => $message,
            'type' => $type->value,
        ]));
        $this->redis->expire("progress:task:{$this->uuid}:logs", self::TTL);
    }

    protected function finishProgress(ColorType $type, string $message): void
    {
        $this->createBaseKeys();

        $startedAt = intval($this->redis->get("progress:task:{$this->uuid}:startedAt"));
        $duration = time() - $startedAt;
        $this->addLogProgress('Duration', gmdate("H:i:s", $duration), ColorType::Nothing);


        $timezone = Current::hasUser() ? Current::getUser()->getTimezone() : new DateTimeZone('UTC');
        $now = new DateTimeImmutable('now', $timezone);
        $this->addLogProgress('Finished at', $now->format('Y-m-d H:i:s T'), ColorType::Nothing);

        $this->redis->setex("progress:task:{$this->uuid}:finish", self::TTL, json_encode([
            'message' => $message,
            'type' => $type->value,
        ]));
    }

    private function createBaseKeys(): void
    {
        if (!$this->redis->exists("progress:task:{$this->uuid}:current")) {
            $this->setCurrentProgress(0);
        }
    }

    protected function useCurrentContext(): bool
    {
        return true;
    }

    protected function channel(): string
    {
        return 'default';
    }

    abstract protected function getTimeLimit(): int;

    abstract protected static function name(): string;

}