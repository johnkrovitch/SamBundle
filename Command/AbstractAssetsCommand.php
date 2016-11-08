<?php

namespace JK\SamBundle\Command;

 use Exception;
 use JK\Sam\Filter\FilterBuilder;
 use JK\Sam\Filter\FilterInterface;
 use JK\Sam\Task\Task;
 use JK\Sam\Task\TaskBuilder;
 use Symfony\Component\Console\Command\Command;
 use Symfony\Component\Console\Input\InputInterface;
 use Symfony\Component\Console\Style\SymfonyStyle;
 use Symfony\Component\DependencyInjection\ContainerAwareInterface;
 use Symfony\Component\DependencyInjection\ContainerInterface;
 use Symfony\Component\Yaml\Yaml;

 abstract class AbstractAssetsCommand extends Command implements ContainerAwareInterface
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
      * @param InputInterface $input
      *
      * @return Task[]
      */
     protected function buildTasks(InputInterface $input)
     {
         $this->io->text('- Building tasks...');
         $builder = new TaskBuilder($this->debug);

         $configuration = $this->loadConfiguration($input);
         $tasks = $builder->build($configuration);

         $this->io->text('- Tasks build !');
         $this->io->newLine();

         return $tasks;
     }

     /**
      * @param InputInterface $input
      *
      * @return string[]
      */
     protected function loadConfiguration(InputInterface $input)
     {
         if ($input->hasOption('config') && $input->getOption('config')) {
             $configuration = $this->loadConfigurationFile($input->getOption('config'));
         } else {
             $configuration = $this
                 ->container
                 ->getParameter('jk.assets.tasks');
         }

         return $configuration;
     }

     /**
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
      * @return FilterInterface[]
      */
     protected function buildFilters()
     {
         $this->io->text('- Building filters...');
         $builder = new FilterBuilder($this->container->get('event_dispatcher'));
         $filters = $builder->build(
             $this
                 ->container
                 ->getParameter('jk.assets.filters')
         );
         $this->io->text('- Filters build !');
         $this->io->newLine();

         return $filters;
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
