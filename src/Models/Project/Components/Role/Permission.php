<?php
/**
 * Created for lokilizer
 * Date: 2025-02-06 00:19
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Models\Project\Components\Role;

enum Permission
{

    case MANAGE_USERS;
    case MANAGE_PROJECT_SETTINGS;
    case MANAGE_LANGUAGES;
    case MANAGE_GLOSSARY;
    case MANAGE_LLM;

    case ALERT_MESSAGE;

    case FILE_UPLOADS;
    case FILE_DOWNLOADS;

    case BACKUP_MAKE;
    case BACKUP_RESTORE;

    case TRANSLATE;

    case BATCH_MODIFY;

    case BATCH_AI;

    case BATCH_HISTORY;


}