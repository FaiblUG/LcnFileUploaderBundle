<?php

namespace Lcn\FileUploaderBundle\Services;

class FileUploader
{

    /**
     * @var FileManager
     */
    protected $fileManager;

    /**
     * @var array
     */
    protected $options;

    public function __construct($options)
    {
        $this->fileManager = $options['file_manager'];
        $this->options = $options;
    }

    /**
     * Get a list of files prepended by the web base path.
     */
    public function getFileUrls($uploadFolderName, $size = null)
    {
        if ($size) {
            $sizeFolderName = $this->getSizeConfig($size, 'folder');
        }
        else {
            $sizeFolderName = $this->getOriginalFolderName();
        }

        $urlPrefix = $this->getWebBasePath().DIRECTORY_SEPARATOR.$uploadFolderName.DIRECTORY_SEPARATOR.$sizeFolderName.DIRECTORY_SEPARATOR;

        $files = $this->getFilenames($uploadFolderName);
        foreach ($files as $idx => $file) {
            $files[$idx] = $urlPrefix.$file;
        }

        return $files;
    }


    public function getFileBasePath() {
        return $this->options['file_base_path'];
    }

    public function getWebBasePath() {
        return $this->options['web_base_path'];
    }

    public function getTempFileBasePath() {
        return $this->options['temp_file_base_path'];
    }

    public function getTempWebBasePath() {
        return $this->options['temp_web_base_path'];
    }

    /**
     * Get a list of files already present.
     */
    public function getFilenames($uploadFolderName)
    {
        $directory = $this->options['file_base_path'].DIRECTORY_SEPARATOR.$uploadFolderName.DIRECTORY_SEPARATOR.$this->getOriginalFolderName();

        return $this->fileManager->getFiles($directory);
    }

    /**
     * Get a list of temporary files.
     */
    public function getTempFiles($uploadFolderName)
    {
        $directory = $this->options['temp_file_base_path'].DIRECTORY_SEPARATOR.$uploadFolderName.DIRECTORY_SEPARATOR.$this->getOriginalFolderName();

        return $this->fileManager->getFiles($directory);
    }

    /**
     * Remove the given folder
     */
    public function removeFiles($uploadFolderName)
    {
        $directory = $this->options['file_base_path'].DIRECTORY_SEPARATOR.$uploadFolderName;

        $this->fileManager->removeFiles($directory);
    }

    /**
     * Removes temporary files and folders older than a given minimum age (in minutes)
     */
    public function removeOldTemporaryFiles($minAgeInMinutes)
    {
        $directory = $this->options['temp_file_base_path'];

        $this->fileManager->removeOldFiles($directory, $minAgeInMinutes);
    }

    /**
     * Sync files from one temp folder to original folder
     *
     * @param $folderName
     * @return void
     */
    public function syncFilesFromTemp($folderName) {
        $this->fileManager->syncFiles(array(
            'from_folder' => $this->options['temp_file_base_path'].DIRECTORY_SEPARATOR.$folderName,
            'to_folder' => $this->options['file_base_path'].DIRECTORY_SEPARATOR.$folderName,
            'remove_from_folder' => true,
            'create_to_folder' => true,
        ));
    }

    /**
     * Sync existing files from one original folder to temp folder
     *
     * @param $folderName
     * @return void
     */
    public function syncFilesToTemp($folderName)
    {
        $this->fileManager->syncFiles(array(
            'from_folder' => $this->options['file_base_path'].DIRECTORY_SEPARATOR.$folderName,
            'to_folder' => $this->options['temp_file_base_path'].DIRECTORY_SEPARATOR.$folderName,
            'create_to_folder' => true,
        ));
    }

    /**
     * Handles a file upload. Call this from an action, after validating the user's
     * right to upload and delete files and determining your 'folder' option. A good
     * example:
     *
     * $id = $this->getRequest()->get('id');
     * // Validate the id, make sure it's just an integer, validate the user's right to edit that 
     * // object, then...
     * $this->get('lcn.file_uploader').handleFileUpload(array('folder' => 'photos/' . $id))
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

        $tempFilePath = $options['temp_file_base_path'] . '/' . $options['folder'];
        $tempWebPath = $options['temp_web_base_path'] . '/' . $options['folder'];

        foreach ($sizes as $index => $size)
        {
            $sizes[$index]['upload_dir'] = $tempFilePath . '/' . $size['folder'] . '/';;
            $sizes[$index]['upload_url'] = $tempWebPath . '/' . $size['folder'] . '/';
            $sizes[$index]['no_cache'] = true;
        }

        $uploadDir = $tempFilePath . '/' . $this->getOriginalFolderName() . '/';;
        $uploadUrl = $tempWebPath . '/' . $this->getOriginalFolderName() . '/';

        foreach ($sizes as $size)
        {
            @mkdir($size['upload_dir'], 0777, true);
        }

        @mkdir($uploadDir, 0777, true);

        new $this->options['upload_handler_class'](
            array(
                'file_namer' => $options['file_namer'],
                'upload_dir' => $uploadDir, 
                'upload_url' => $uploadUrl,
                'image_versions' => $sizes,
                'accept_file_types' => $allowedExtensionsRegex,
                'max_number_of_files' => $options['max_number_of_files'],
            ));

        // Without this Symfony will try to respond; the BlueImp upload handler class already did,
        // so it's time to hush up
        exit(0);
    }

    public function getOriginalFolderName() {
        return $this->options['original']['folder'];
    }

    public function getThumbnailFolderName() {
        return $this->getSizeConfig('thumbnail', 'folder');
    }

    protected function getSizeConfig($size, $key, $default = null) {
        $this->validateSize($size);
        $sizeConfig = $this->options['sizes'][$size];

        if (array_key_exists($key, $sizeConfig)) {
            return $sizeConfig[$key];
        }

        return $default;
    }

    protected function validateSize($size) {
        if (!array_key_exists($size, $this->options['sizes'])) {
            throw new FileUploaderException('Invalid size: '.$size);
        }
    }
}
