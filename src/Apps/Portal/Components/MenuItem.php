<?php
/**
 * Created for lokilizer
 * Date: 2025-02-06 01:51
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Apps\Portal\Components;

use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Models\Project\Components\Role\Permission;

readonly class MenuItem
{

    public function __construct(
        public string $route,
        public ?Permission $permission = null
    )
    {

    }

    public function isVisible(): bool
    {
        if (is_null($this->permission)) {
            return true;
        }
        return Current::can($this->permission);
    }

}