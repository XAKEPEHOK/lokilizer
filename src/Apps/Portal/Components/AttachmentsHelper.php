<?php
/**
 * Created for lokilizer
 * Date: 08.08.2024 01:57
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Apps\Portal\Components;

use XAKEPEHOK\Lokilizer\Components\Current;
use Psr\Http\Message\UploadedFileInterface;
use XAKEPEHOK\Path\Path;

class AttachmentsHelper
{

    public static function upload(UploadedFileInterface $uploadedFile): Path
    {
        $clientFileName = $uploadedFile->getClientFilename();
        $clientFileName = preg_replace('~[\\\/*<>#:]+~ui', '', $clientFileName);
        $clientFileName = preg_replace('~\.{2,}~ui', '', $clientFileName);
        $clientFileName = preg_replace('~^\.~ui', '', $clientFileName);

        $id = Current::getProject()->id();
        $path = (new Path(substr($id, -1)))
            ->down($id)
            ->down(date('Y/m/d/Hi'))
        ;
        $path = $path->down($clientFileName);

        $fsPath = self::getFileSystemPath()->down($path);
        $dirPath = $fsPath->up();
        if (!is_dir($dirPath)) {
            mkdir($dirPath, 0755, true);
        }

        $uploadedFile->moveTo("$fsPath");
        return $path;
    }

    public static function getUri(Path $path): string
    {
        return (new Path('uploads'))->down($path);
    }

    public static function getDiskPath(Path $path): string
    {
        return self::getFileSystemPath()->down($path);
    }

    public static function delete(Path $path): void
    {
        $fsPath = self::getFileSystemPath()->down($path);
        if (file_exists($fsPath)) {
            unlink($fsPath);
        }
    }

    private static function getFileSystemPath(): Path
    {
        return Path::root()->down('web/uploads/');
    }

}