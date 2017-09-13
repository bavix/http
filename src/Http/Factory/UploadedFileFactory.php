<?php

namespace Bavix\Http\Factory;

use Interop\Http\Factory\UploadedFileFactoryInterface;
use Bavix\Http\UploadedFile;

class UploadedFileFactory implements UploadedFileFactoryInterface
{
    /**
     * @param resource|string $file
     * @param int            $size
     * @param int             $error
     * @param null            $clientFilename
     * @param null            $clientMediaType
     *
     * @return UploadedFile
     */
    public function createUploadedFile(
        $file,
        $size = null,
        $error = \UPLOAD_ERR_OK,
        $clientFilename = null,
        $clientMediaType = null
    )
    {
        if ($size === null)
        {
            if (is_string($file))
            {
                $size = (int)filesize($file);
            }
            else
            {
                $stats = fstat($file);
                $size  = $stats['size'];
            }
        }

        return new UploadedFile($file, $size, $error, $clientFilename, $clientMediaType);
    }
}
