<?php

namespace XAKEPEHOK\Lokilizer\Components\Db\Repo;

use DiBify\DiBify\Mappers\MapperInterface;

abstract class Repository extends \DiBify\DiBify\Repository\Repository
{

    protected MapperInterface $mapper;

}