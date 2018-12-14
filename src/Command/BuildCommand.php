<?php

namespace JK\SamBundle\Command;

use Exception;
use JK\Sam\File\Locator;
use JK\Sam\File\Normalizer;
use JK\Sam\Task\Task;
use JK\Sam\Task\TaskRunner;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

class BuildCommand extends AbstractCommand implements ContainerAwareInterface
{
    /**
     * Configure the task name.
     */
    protected function configure()
    {
        $this
            ->setName('jk:assets:build')
            ->setDescription('Build the assets according to your assets configuration ("jk_assets")')
            ->addOption('config', 'c', InputOption::VALUE_OPTIONAL,
                'If defined, this file will be used to load the assets configuration. It should be an yml file '
                .'containing an array of tasks and filters.
                    jk.assets:
                    ____tasks:
                    ________// your configuration
                    ________...
                    ____filters: ...
                '
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     *
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
        $this
            ->io
            ->title('Symfony PHP Assets Manager');

        // load configuration from container or given file path
        $configuration = $this->loadConfiguration($input);

        // get debug mode
        $this->debug = $configuration['debug'];

        if ($this->debug) {
            $this->io->note('Debug Mode...');
        }

        // build tasks to run
        $tasks = $this->buildTasks($configuration['tasks']);

        // build required filters
        $filters = $this->buildFilters($configuration['filters']);

        // run task with configured filter
        $normalizer = new Normalizer($this->container->getParameter('kernel.root_dir').'/../');
        $locator = new Locator($normalizer);

        // create the runner
        $runner = new TaskRunner(
            $filters,
            $locator,
            $this->debug
        );
        $this->io->text('- Running tasks...');

        // run tasks
        foreach ($tasks as $task) {
            $this->runManagedTask($runner, $task);
        }

        // display end message
        $this->io->success('Assets build end');
    }

    /**
     * @param TaskRunner $runner
     * @param Task $task
     * @throws Exception
     */
    protected function runManagedTask(TaskRunner $runner, Task $task)
    {
        $notificationSubscriber = $this
            ->container
            ->get('jk.assets.notification_subscriber');

        try {
            $this->io->text('- Running '.$task->getName());
            $runner->run($task);

            foreach ($notificationSubscriber->getNotifications() as $notification) {
                $this->io->text('  <info>x</info> '.$notification);
            }
            $notificationSubscriber->clearNotifications();
            $this->io->newLine();

        } catch (Exception $e) {

            if ($this->debug) {
                foreach ($notificationSubscriber->getNotifications() as $index => $notification) {

                    if ($index == count($notificationSubscriber->getNotifications())) {
                        $notification = '<error>x</error> '.$notification;
                    } else {
                        $notification = '<info>x</info>'.$notification;
                    }
                    $this->io->text($notification);
                }
                $notificationSubscriber->clearNotifications();
            }

            throw new Exception(
                'An error has been encountered during the execution of the task '.$task->getName()."\n"
                .$e->getMessage(),
                0,
                $e
            );
        }
    }
}
