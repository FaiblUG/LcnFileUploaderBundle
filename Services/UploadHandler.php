<?php

namespace Lcn\FileUploaderBundle\Services;


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

        if (array_key_exists('file_namer', $this->options) && $this->options['file_namer'] instanceof FileNamerInterface) {
            $name = $this->options['file_namer']->getFilename($name);
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
