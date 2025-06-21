<?php
/**
 * Created for lokilizer
 * Date: 21.07.2024 21:43
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Components\Db\Storage\Mongo;

abstract class MongoStorage extends \DiBify\Storage\MongoDB\MongoStorage
{

    public function scopeKey(): string
    {
        return 'project';
    }

}