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
    private $uploader;

    /**
     * @var string
     */
    private $webBasePath;

    /**
     * @param ContainerInterface $container
     * @param string $webBasePath
     */
    public function __construct(ContainerInterface $container, $webBasePath)
    {
        $this->uploader = $container->get('lcn.file_uploader');
        $this->webBasePath = $webBasePath;
    }

    public function getFunctions()
    {
        return array(
            'lcn_file_uploader_get_files' => new Twig_Function_Method($this, 'getUploadedFiles'),
            'lcn_file_uploader_get_web_path' => new Twig_Function_Method($this, 'getWebPath'),
        );
    }

    public function getUploadedFiles($folder)
    {
        return $this->uploader->getFiles(array('folder' => $folder));
    }

    public function getWebPath($folder)
    {
        return $this->webBasePath.'/'.$folder;
    }

    public function getName()
    {
        return 'lcn_file_uploader';
    }
}
