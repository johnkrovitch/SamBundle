<?php

namespace JK\SamBundle\Configuration\Loader;

use Exception;
use JK\SamBundle\DependencyInjection\Configuration;
use SplFileInfo;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class ConfigurationLoader
{
    public function loadFromContainer(ContainerInterface $container)
    {
        return $container->getParameter('jk.assets');
    }

    public function loadFromFile($path)
    {
        // the file should exists
        $fileSystem = new Filesystem();

        if (!$fileSystem->exists($path)) {
            throw new FileNotFoundException();
        }
        // the file should be a yml file
        $configurationFile = new SplFileInfo($path);

        if ($configurationFile->getExtension() !== 'yml') {
            throw new Exception('Only yml are allowed for assets configuration loading');
        }
        // parse configuration using Symfony processor
        $configuration = Yaml::parse(file_get_contents($path));
        $configurationDI = new Configuration();
        $processor = new Processor();

        return $processor->processConfiguration($configurationDI, $configuration);
    }
}
