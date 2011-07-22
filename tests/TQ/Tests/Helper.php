<?php
namespace TQ\Tests;

class Helper
{
    public static function removeDirectory($path)
    {
        if (!file_exists($path)) {
            return;
        }

        if (!is_dir($path)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a directory', $path));
        }

        $dirIt  = new \RecursiveDirectoryIterator($path,
              \RecursiveDirectoryIterator::SKIP_DOTS
            | \RecursiveDirectoryIterator::KEY_AS_PATHNAME
            | \RecursiveDirectoryIterator::CURRENT_AS_FILEINFO
        );
        $it = new \RecursiveIteratorIterator($dirIt,
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($it as $p => $f) {
            if ($f->isDir()) {
                rmdir($p);
            } else if ($f->isFile()) {
                unlink($p);
            }
        }
        rmdir($path);
    }
}