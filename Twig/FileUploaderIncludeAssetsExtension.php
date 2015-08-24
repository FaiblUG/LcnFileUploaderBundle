<?php

namespace Lcn\FileUploaderBundle\Twig;

use Lcn\FileUploaderBundle\Services\FileUploader;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig_Extension;
use Twig_Function_Method;

class FileUploaderIncludeAssetsExtension extends Twig_Extension
{
    /**
     * @var \Lcn\IncludeAssetsBundle\Service\IncludeAssets | null
     */
    private $includeAssets;

    private $jsMain;

    private $cssMain;

    private $cssTheme;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container, $jsMain, $cssMain, $cssTheme)
    {
        if ($container->has('lcn.include_assets')) {
            $this->includeAssets = $container->get('lcn.include_assets');
            $this->jsMain = $jsMain;
            $this->cssMain = $cssMain;
            $this->cssTheme = $cssTheme;
        }
    }

    public function getFunctions()
    {
        return array(
            'lcn_file_uploader_include_assets' => new Twig_Function_Method($this, 'includeAssets'),
        );
    }

    public function includeAssets()
    {
        if ($this->includeAssets) {
            $this->includeAssets->useJavascript($this->jsMain);
            $this->includeAssets->useStylesheet($this->cssMain, 'middle', true);
            $this->includeAssets->useStylesheet($this->cssTheme, 'middle', true);
        }
    }

    public function getName()
    {
        return 'lcn_file_uploader_include_assets';
    }
}
