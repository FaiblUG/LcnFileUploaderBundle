<?php

namespace Lcn\FileUploaderBundle\Services;

use Symfony\Component\HttpFoundation\Request;

class FileUploader
{
    protected $options;

    /**
     * @var Request
     */
    protected $request;

    public function __construct($options)
    {
        $this->request = $options['container']->get('request');
        unset($this->options['container']);
        $this->options = $options;
    }

    /**
     * Get a list of files already present. The 'folder' option is required. 
     * If you pass consistent options to this method and handleFileUpload with
     * regard to paths, then you will get consistent results.
     */
    public function getFiles($options = array())
    {
        return $this->options['file_manager']->getFiles($options);
    }

    /**
     * Remove the folder specified by 'folder' and its contents.
     * If you pass consistent options to this method and handleFileUpload with
     * regard to paths, then you will get consistent results.
     */
    public function removeFiles($options = array())
    {
        return $this->options['file_manager']->removeFiles($options);
    }

    /**
     * Sync existing files from one folder to another. The 'fromFolder' and 'toFolder'
     * options are required. As with the 'folder' option elsewhere, these are appended
     * to the file_base_path for you, missing parent folders are created, etc. If 
     * 'fromFolder' does not exist no error is reported as this is common if no files
     * have been uploaded. If there are files and the sync reports errors an exception
     * is thrown.
     * 
     * If you pass consistent options to this method and handleFileUpload with
     * regard to paths, then you will get consistent results.
     */
    public function syncFiles($options = array())
    {
        return $this->options['file_manager']->syncFiles($options);
    }

    /**
     * Handles a file upload. Call this from an action, after validating the user's
     * right to upload and delete files and determining your 'folder' option. A good
     * example:
     *
     * $id = $this->getRequest()->get('id');
     * // Validate the id, make sure it's just an integer, validate the user's right to edit that 
     * // object, then...
     * $this->get('lcn.file_upload').handleFileUpload(array('folder' => 'photos/' . $id))
     * 
     * DOES NOT RETURN. The response is generated in native PHP by BlueImp's UploadHandler class.
     *
     * Note that if %file_uploader.file_path%/$folder already contains files, the user is 
     * permitted to delete those in addition to uploading more. This is why we use a
     * separate folder for each object's associated files.
     *
     * Any passed options are merged with the service parameters. You must specify
     * the 'folder' option to distinguish this set of uploaded files
     * from others.
     *
     */
    public function handleFileUpload($options = array())
    {
        if (!isset($options['folder']))
        {
            throw new \Exception("You must pass the 'folder' option to distinguish this set of files from others");
        }

        $options = array_merge($this->options, $options);

        $allowedExtensions = $options['allowed_extensions'];

        // Build a regular expression like /(\.gif|\.jpg|\.jpeg|\.png)$/i
        $allowedExtensionsRegex = '/(' . implode('|', array_map(function($extension) { return '\.' . $extension; }, $allowedExtensions)) . ')$/i';

        $sizes = (isset($options['sizes']) && is_array($options['sizes'])) ? $options['sizes'] : array();

        $filePath = $options['file_base_path'] . '/' . $options['folder'];
        $webPath = $options['web_base_path'] . '/' . $options['folder'];

        foreach ($sizes as $index => $size)
        {
            $sizes[$index]['upload_dir'] = $filePath . '/' . $size['folder'] . '/';
            $sizes[$index]['upload_url'] = $webPath . '/' . $size['folder'] . '/';
            $sizes[$index]['no_cache'] = true;
        }

        $originals = $options['originals'];

        $uploadDir = $filePath . '/' . $originals['folder'] . '/';

        foreach ($sizes as $size)
        {
            @mkdir($size['upload_dir'], 0777, true);
        }

        @mkdir($uploadDir, 0777, true);

        $upload_handler = new \Lcn\FileUploaderBundle\BlueImp\UploadHandler(
            array(
                'upload_dir' => $uploadDir, 
                'upload_url' => $webPath . '/' . $originals['folder'] . '/',
                'script_url' => $this->request->getUri(),
                'image_versions' => $sizes,
                'accept_file_types' => $allowedExtensionsRegex,
                'max_number_of_files' => $options['max_number_of_files'],
            ));

        // Without this Symfony will try to respond; the BlueImp upload handler class already did,
        // so it's time to hush up
        exit(0);
    }
}
