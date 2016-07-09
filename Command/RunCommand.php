<?php

namespace JK\SamBundle\Command;

use Exception;
use JK\Sam\File\Locator;
use JK\Sam\File\Normalizer;
use JK\Sam\Filter\FilterBuilder;
use JK\Sam\Filter\FilterInterface;
use JK\Sam\Task\Task;
use JK\Sam\Task\TaskBuilder;
use JK\Sam\Task\TaskRunner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RunCommand extends Command implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var boolean
     */
    protected $debug = false;

    /**
     * @var SymfonyStyle
     */
    protected $io;

    /**
     *
     */
    protected function configure()
    {
        $this
            ->setName('jk:assets:build')
            ->setDescription('Build the assets according to your tasks configuration ("jk_assets")')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->io->title('Symfony PHP Assets Manager');

        // get debug mode
        $this->debug = $this
            ->container
            ->getParameter('jk.assets.debug');

        if ($this->debug) {
            $this->io->note('Debug Mode...');
        }

        // build tasks to run
        $tasks = $this->buildTasks();

        // build required filters
        $filters = $this->buildFilters();

        // run task with configured filter
        $normalizer = new Normalizer($this->container->getParameter('kernel.root_dir').'/../');
        $runner = new TaskRunner($filters, new Locator($normalizer), $this->debug);
        $this->io->text('- Running tasks...');

        // run tasks
        foreach ($tasks as $index => $task) {
            $this->runManagedTask($runner, $task);
        }

        // display end message
        $this->io->success('Assets build end');
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

    /**
     * @return Task[]
     */
    protected function buildTasks()
    {
        $this->io->text('- Building tasks...');
        $builder = new TaskBuilder($this->debug);
        $tasks = $builder->build(
            $this
                ->container
                ->getParameter('jk.assets.tasks')
        );
        $this->io->text('- Tasks build !');
        $this->io->newLine();

        return $tasks;
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
