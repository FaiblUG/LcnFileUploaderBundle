<?php

namespace Lcn\FileUploaderBundle\Services;


class FileNamerHash implements FileNamerInterface
{

    /**
     * @var string
     */
    protected $salt;

    /**
     * @param string $salt
     */
    public function __construct($salt) {
        $this->salt = $salt;
    }

    /**
     * Hash filename for better privacy
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
            $basename = implode('-', $filenameParts);

            return md5($basename.$this->getSalt()).'.'.$extension;
        }


        return md5($filename.$this->getSalt());
    }

    /**
     * @param string $value
     * @return string
     */
    protected function getSalt() {
        return $this->salt;
    }

}
