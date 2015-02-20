<?php

namespace Lcn\FileUploaderBundle\Services;


class FileNamerSanitize implements FileNamerInterface
{
    /**
     * Hash filename
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
            $basename = implode('.', $filenameParts);

            return $this->sanitize($basename).'.'.$this->sanitize($extension);
        }


        return $this->sanitize($filename);
    }

    protected function sanitize($dirty) {
        $strip = array("~", "`", "!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "_", "=", "+", "[", "{", "]",
          "}", "\\", "|", ";", ":", "\"", "'", "&#8216;", "&#8217;", "&#8220;", "&#8221;", "&#8211;", "&#8212;",
          "â€”", "â€“", ",", "<", ".", ">", "/", "?");
        $clean = trim(str_replace($strip, "", strip_tags($dirty)));
        $clean = preg_replace('/\s+/', "-", $clean);
        $clean = preg_replace("/[^a-zA-Z0-9]/", "-", $clean);
        $clean = (function_exists('mb_strtolower')) ? mb_strtolower($clean, 'UTF-8') : strtolower($clean);

        return $clean;
    }

}
