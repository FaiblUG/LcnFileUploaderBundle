<?php

namespace Lcn\FileUploaderBundle\Services;

use Lcn\FileUploaderBundle\Exception\FileUploaderException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class FileUploader
{

    /**
     * @var ContainerInterface
     */
    protected $container;

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
        $this->container = $options['container'];
        $this->fileManager = $options['file_manager'];
        $this->options = $options;
    }

    /**
     * Get a list of file urls for the files in the given upload folder and for the given image size (optional)
     *
     * @param string $uploadFolderName
     * @param string|null $size
     * @return array
     */
    public function getFileUrls($uploadFolderName, $size = null)
    {
        $filenames = $this->getFilenames($uploadFolderName);
        foreach ($filenames as $idx => $filename) {
            $filenames[$idx] = $this->getFileUrl($uploadFolderName, $filename, $size);
        }

        return $filenames;
    }

    /**
     * Get file url for the given file in the given upload folder and for the given image size (optional)
     *
     * @param string $uploadFolderName
     * @param string $filename
     * @param string|null $size
     * @return string
     */
    public function getFileUrl($uploadFolderName, $filename, $size = null)
    {
        $urlPrefix = $this->getWebBasePath().DIRECTORY_SEPARATOR.$uploadFolderName;

        return $this->getFileUrlForPrefix($urlPrefix, $filename, $size);
    }

    /**
     * Get file url for the given temporary file in the given upload folder and for the given image size (optional)
     *
     * @param string $uploadFolderName
     * @param string $filename
     * @param string|null $size
     * @return string
     */
    public function getTempFileUrl($uploadFolderName, $filename, $size = null)
    {
        $urlPrefix = $this->getTempWebBasePath().DIRECTORY_SEPARATOR.$uploadFolderName;

        return $this->getFileUrlForPrefix($urlPrefix, $filename, $size);
    }

    /**
     * Get file url for the given temporary file in the given upload folder and for the given image size (optional)
     *
     * @param string $urlPrefix
     * @param string $uploadFolderName
     * @param string $filename
     * @param string|null $size
     * @return string
     */
    public function getFileUrlForPrefix($urlPrefix, $filename, $size = null)
    {
        $sizeFolderName = $this->getFolderNameForSize($size, true);

        return $this->getAbsoluteUrlForLocalUrl($size, $urlPrefix.DIRECTORY_SEPARATOR.$sizeFolderName.DIRECTORY_SEPARATOR.$filename);
    }

    protected function getFolderNameForSize($size, $useOriginalAsFallback = false)
    {
        $sizeFolderName = $this->getSizeConfig($size, 'folder');
        if (!$sizeFolderName && $useOriginalAsFallback && $size !== 'original') {
            $sizeFolderName = $this->getSizeConfig('original', 'folder');
        }


        if (!$sizeFolderName) {
            throw new FileUploaderException('Could not determine upload folder name for size: '.$size);
        }

        return $sizeFolderName;
    }

    /**
     * @param array $proxyConfig
     * @param string $localUrl
     */
    private function getAbsoluteUrlForLocalUrl($size = null, $localUrl) {
        $proxyConfig = $this->getSizeConfig($size, 'proxy');
        if ($proxyConfig && $proxyConfig['enabled']) {
            $proxyUrl = $proxyConfig['url'];
            if ($proxyConfig['parameters'] && is_array($proxyConfig['parameters'])) {
                $parameters = $this->getProxyParameters($proxyConfig['parameters'], $size);
                $proxyUrl = $proxyUrl.(false === strpos($proxyUrl, '?') ? '?' : '&').http_build_query($parameters);
            }

            return str_replace('~imageUrl~', $localUrl, $proxyUrl);
        }
        else {
            return $this->getRequest()->getSchemeAndHttpHost().$localUrl;
        }
    }

    private function getProxyParameters($parameters, $size) {
        $result = array();
        $maxWidth = $this->getSizeConfig($size, 'max_width');
        $maxHeight = $this->getSizeConfig($size, 'max_height');
        foreach ($parameters as $key => $value) {
            $result[$key] = str_replace(array('~max_width~', '~max_height~'), array($maxWidth, $maxHeight), $value);
        }

        return $result;
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
     * Get a list of temporary files already present.
     */
    public function getTempFilenames($uploadFolderName)
    {
        $directory = $this->options['temp_file_base_path'].DIRECTORY_SEPARATOR.$uploadFolderName.DIRECTORY_SEPARATOR.$this->getOriginalFolderName();

        return $this->fileManager->getFiles($directory);
    }

    /**
     * Get a list of temporary files.
     */
    public function getTempFiles($uploadFolderName)
    {
        $result = array();

        $filenames = $this->getTempFilenames($uploadFolderName);
        foreach ($filenames as $filename) {
            $result[] = array(
                'thumbnailUrl' => $this->isValidImageFilename($filename) ? $this->getTempFileUrl($uploadFolderName, $filename, 'thumbnail') : null,
                'url' => $this->getTempFileUrl($uploadFolderName, $filename),
                'name' => $filename,
            );
        }


        return $result;
    }

    protected function isValidImageFilename($filename) {
        return !!preg_match($this->options['image_file_extension_test_regex'], $filename);
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

        $imageVersions = array();
        foreach ($sizes as $index => $size)
        {
            if (isset($size['folder'])) {
                $imageVersion = $size;
                $imageVersion['no_cache'] = true;
                $imageVersion['upload_dir'] = $tempFilePath . '/' . $size['folder'] . '/';
                $imageVersion['upload_url'] = $tempWebPath . '/' . $size['folder'] . '/';
                @mkdir($imageVersion['upload_dir'], 0777, true);
                $imageVersions[$index] = $imageVersion;
            }

        }

        $uploadDir = $tempFilePath . '/' . $this->getOriginalFolderName() . '/';;
        $uploadUrl = $tempWebPath . '/' . $this->getOriginalFolderName() . '/';
        @mkdir($uploadDir, 0777, true);

        new $this->options['upload_handler_class'](
            $this,
            array(
                'file_namers' => $options['file_namers'],
                'upload_folder_name' => $options['folder'],
                'upload_dir' => $uploadDir, 
                'upload_url' => $uploadUrl,
                'image_versions' => $imageVersions,
                'accept_file_types' => $allowedExtensionsRegex,
                'max_number_of_files' => $options['max_number_of_files'],
                'max_file_size' => $options['max_file_size'],
            ));

        // Without this Symfony will try to respond; the BlueImp upload handler class already did,
        // so it's time to hush up
        exit(0);
    }

    public function setOption($key, $value)
    {
        $this->options[$key] = $value;
    }

    public function getOption($key, $default = null)
    {
        return array_key_exists($key, $this->options) ? $this->options[$key] : $default;
    }

    protected function getOriginalFolderName() {
        return $this->getSizeConfig('original', 'folder');
    }

    protected function getSizeConfig($size, $key, $default = null) {
        if ($size === null) {
            $size = 'original';
        }

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

    /**
     * @return Request
     */
    protected function getRequest() {
        return $this->container->get('request');
    }
}
