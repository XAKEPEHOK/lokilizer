<?php

namespace XAKEPEHOK\Lokilizer\Models\Project\Db;

use DiBify\DiBify\Mappers\EnumMapper;
use DiBify\DiBify\Mappers\IntMapper;
use DiBify\DiBify\Mappers\NullOrMapper;
use DiBify\DiBify\Mappers\ObjectMapper;
use XAKEPEHOK\Lokilizer\Components\Db\Mappers\LanguageMapper;
use XAKEPEHOK\Lokilizer\Components\Parsers\FileFormatter;
use XAKEPEHOK\Lokilizer\Models\Project\Components\EOLFormat;
use XAKEPEHOK\Lokilizer\Models\Project\Components\PlaceholderFormat;
use XAKEPEHOK\Lokilizer\Models\Project\Components\Role\Role;
use XAKEPEHOK\Lokilizer\Models\Project\Components\UserRole;
use XAKEPEHOK\Lokilizer\Models\Project\Project;
use DiBify\DiBify\Mappers\ArrayMapper;
use DiBify\DiBify\Mappers\IdMapper;
use DiBify\DiBify\Mappers\ModelMapper;
use DiBify\DiBify\Mappers\ReferenceMapper;
use DiBify\DiBify\Mappers\StringMapper;

class ProjectMapper extends ModelMapper
{

    public function __construct()
    {
        parent::__construct(Project::class, [
            'id' => IdMapper::getInstance(),
            'name' => StringMapper::getInstance(),
            'users' => new ArrayMapper(new ObjectMapper(UserRole::class, [
                'user' => ReferenceMapper::getInstanceLazy(),
                'role' => new EnumMapper(Role::class, IntMapper::getInstance()),
                'languages' => new ArrayMapper(LanguageMapper::getInstance())
            ])),
            'primaryLanguage' => LanguageMapper::getInstance(),
            'secondaryLanguage' => new NullOrMapper(LanguageMapper::getInstance()),
            'defaultLLM' => ReferenceMapper::getInstanceEager(),
            'placeholders' => new EnumMapper(PlaceholderFormat::class, StringMapper::getInstance()),
            'eol' => new EnumMapper(EOLFormat::class, StringMapper::getInstance()),
            'fileFormatter' => new EnumMapper(FileFormatter::class, StringMapper::getInstance()),
        ]);
    }

}