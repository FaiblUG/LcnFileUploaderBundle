<?php

namespace Lcn\FileUploaderBundle\Twig;

use Lcn\FileUploaderBundle\Services\FileUploader;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig_Extension;
use Twig_Function_Method;

class FileUploaderExtension extends Twig_Extension
{
    /**
     * @var FileUploader
     */
    private $fileUploader;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(FileUploader $fileUploader)
    {
        $this->fileUploader = $fileUploader;
    }

    public function getFunctions()
    {
        return array(
            'lcn_file_uploader_get_temp_files' => new Twig_Function_Method($this, 'getTempFiles'),
            'lcn_file_uploader_get_temp_web_path' => new Twig_Function_Method($this, 'getTempWebPath'),
        );
    }

    public function getTempFiles($uploadFolderName)
    {
        return $this->fileUploader->getTempFiles($uploadFolderName);
    }

    public function getTempWebPath($folder)
    {
        return $this->fileUploader->getTempWebBasePath().DIRECTORY_SEPARATOR.$folder;
    }

    public function getName()
    {
        return 'lcn_file_uploader';
    }
}
