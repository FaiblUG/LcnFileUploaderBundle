<?php

namespace Lcn\FileUploaderBundle\Services;


class UploadHandler extends \Lcn\FileUploaderBundle\BlueImp\UploadHandler
{
    /**
     *
     * Override method: avoud double slashes
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
}
