<?php

namespace JK\SamBundle\Command;

use Exception;
use JK\Sam\Filter\FilterBuilder;
use JK\Sam\Filter\FilterInterface;
use JK\Sam\Task\Task;
use JK\Sam\Task\TaskBuilder;
use JK\SamBundle\Configuration\Loader\ConfigurationLoader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

abstract class AbstractCommand extends Command implements ContainerAwareInterface
{
    /**
     * @var SymfonyStyle
     */
    protected $io;

    /**
     * @var boolean
     */
    protected $debug = false;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Build tasks from the configuration array.
     *
     * @param array $configuration
     *
     * @return Task[]
     */
    protected function buildTasks(array $configuration)
    {
        $this->io->text('- Building tasks...');
        $builder = new TaskBuilder($this->debug);

        $tasks = $builder->build($configuration);

        $this->io->text('- Tasks build !');
        $this->io->newLine();

        return $tasks;
    }

    /**
     * Build the filter according to the configuration array.
     *
     * @param array $configuration
     *
     * @return FilterInterface[]
     */
    protected function buildFilters(array $configuration)
    {
        $this->io->text('- Building filters...');
        $builder = new FilterBuilder($this->container->get('event_dispatcher'));

        $filters = $builder->build($configuration);

        $this->io->text('- Filters build !');
        $this->io->newLine();

        return $filters;
    }

    /**
     * Load the configuration from a yml file.
     *
     * @param $configurationFile
     *
     * @return string[]
     *
     * @throws Exception
     */
    protected function loadConfigurationFile($configurationFile)
    {
        if (!file_exists($configurationFile)) {
            throw new Exception('The configuration yml file '.$configurationFile.' was not found');
        }
        $configuration = Yaml::parse(file_get_contents($configurationFile));

        if (empty($configuration['jk_assets']['tasks'])) {
            throw new Exception('Tasks not found in configuration file '.$configurationFile);
        }

        return $configuration['jk_assets']['tasks'];
    }

    /**
     * Load the configuration from a yml file or the container, according to the given option.
     *
     * @param InputInterface $input
     * @return array
     */
    protected function loadConfiguration(InputInterface $input)
    {
        $loader = new ConfigurationLoader();

        if ($input->hasOption('config') && $file = $input->getOption('config')) {
            $configuration = $loader->loadFromFile($file);
        } else {
            $configuration = $loader->loadFromContainer($this->container);
        }

        return $configuration;
    }

    /**
     * Sets the container.
     *
     * @param ContainerInterface|null $container A ContainerInterface instance or null
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
}
