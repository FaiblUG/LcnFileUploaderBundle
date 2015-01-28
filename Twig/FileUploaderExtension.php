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
    private $tempWebBasePath;

    /**
     * @param ContainerInterface $container
     * @param string $tempWebBasePath
     */
    public function __construct(ContainerInterface $container, $tempWebBasePath)
    {
        $this->uploader = $container->get('lcn.file_uploader');
        $this->tempWebBasePath = $tempWebBasePath;
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
        return $this->uploader->getTempFiles($uploadFolderName);
    }

    public function getTempWebPath($folder)
    {
        return $this->tempWebBasePath.'/'.$folder;
    }

    public function getName()
    {
        return 'lcn_file_uploader';
    }
}
