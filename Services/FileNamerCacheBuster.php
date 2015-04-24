<?php

namespace Lcn\FileUploaderBundle\Services;


class FileNamerCacheBuster implements FileNamerInterface
{

    /**
     * Add timestamp to invalidate caches
     *
     * @param string $filename
     *
     * @return string
     */
    public function getFilename($filename) {
        $filenameParts = explode('.', $filename);

        if (count($filenameParts) > 1) {
            $extension = $filenameParts[count($filenameParts) - 1];
            unset($filenameParts[count($filenameParts) - 1]);
            $filenameParts[] = time();
            $basename = implode('-', $filenameParts);

            return $basename.'.'.$extension;
        }


        return $filename.'-'.time();
    }


}
