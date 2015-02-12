<?php

namespace Lcn\FileUploaderBundle\Services;


class FileNamerOriginal implements FileNamerInterface
{
    /**
     * Hash filename
     *
     * @param string $filename
     *
     * @return string
     */
    public function getFilename($filename) {
        return $filename;
    }

}
