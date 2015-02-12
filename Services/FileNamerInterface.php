<?php

namespace Lcn\FileUploaderBundle\Services;


interface FileNamerInterface
{
    /**
     * Rename filename as needed
     *
     * @param string $filename
     *
     * @return string
     */
    public function getFilename($filename);
}
