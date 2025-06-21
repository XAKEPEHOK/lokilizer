<?php
/**
 * Created for lokilizer
 * Date: 2025-02-12 01:47
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Components\Parsers;

class FileExportRepresentation
{

    public function __construct(
        public mixed $content,
        public string $filename,
        public int $size,
    )
    {
    }

}