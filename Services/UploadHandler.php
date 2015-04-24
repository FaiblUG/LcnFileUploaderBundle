<?php

namespace Lcn\FileUploaderBundle\Services;


use Lcn\FileUploaderBundle\Exception\FileUploaderException;

class UploadHandler extends \Lcn\FileUploaderBundle\BlueImp\UploadHandler
{
    /**
     *
     * Override method: avoid double slashes
     *
     * @param $file_name
     * @param $version
     * @return array
     */
    protected function get_scaled_image_file_paths($file_name, $version) {
        $result = parent::get_scaled_image_file_paths($file_name, $version);
        foreach ($result as $key => $value) {
            $result[$key] = str_replace('//', '/', $value);
        }

        return $result;
    }

    protected function get_file_name($file_path, $name, $size, $type, $error, $index, $content_range) {
        $name = $this->trim_file_name($file_path, $name, $size, $type, $error, $index, $content_range);

        if (array_key_exists('file_namers', $this->options) && is_array($this->options['file_namers'])) {
            foreach($this->options['file_namers'] as $fileNamer) {
                if ($fileNamer instanceof FileNamerInterface) {
                    $name = $fileNamer->getFilename($name);
                }
                else {
                    throw new FileUploaderException('All file namers must implement the FileNamerInterface');
                }
            }
        }

        return $this->get_unique_filename(
          $file_path,
          $this->fix_file_extension($file_path, $name, $size, $type, $error,
            $index, $content_range),
          $size,
          $type,
          $error,
          $index,
          $content_range
        );
    }
}
