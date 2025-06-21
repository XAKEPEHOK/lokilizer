<?php

namespace XAKEPEHOK\Lokilizer\Apps\Console\Components;

use RuntimeException;
use Symfony\Component\Console\Command\Command;
use XAKEPEHOK\Path\Path;

abstract class MutexCommand extends Command
{

    protected function withMutex(callable $function, ?string $mutexFileName = null)
    {
        $mutexName = $mutexFileName ?? $this->getName();
        $mutexFile = Path::root()->down('runtime')->down("{$mutexName}.mutex");
        $mutex = fopen((string)$mutexFile, 'c');

        if (!$mutex) {
            throw new RuntimeException("Can not create mutex file '{$mutexFile}'. No permissions?");
        }

        if (!flock($mutex, LOCK_EX | LOCK_NB)) {
            fclose($mutex);
            throw new RuntimeException("Command '{$this->getName()}' already running");
        }

        try {
            return $function();
        } finally {
            @flock($mutex, LOCK_UN);
            @fclose($mutex);
            @unlink($mutexFile);
        }
    }

}