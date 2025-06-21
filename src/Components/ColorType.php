<?php
/**
 * Created for lokilizer
 * Date: 2025-01-22 01:25
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Components;

enum ColorType: string
{

    case Primary = 'primary';
    case Secondary = 'secondary';
    case Success = 'success';
    case Danger = 'danger';
    case Warning = 'warning';
    case Info = 'info';
    case Nothing = 'nothing';

}
