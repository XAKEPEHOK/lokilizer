<?php
/**
 * Created for lokilizer
 * Date: 15.08.2024 01:45
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer;

use XAKEPEHOK\Lokilizer\Components\PublicExceptionInterface;
use Exception;

class NotFoundException extends Exception implements PublicExceptionInterface
{

}