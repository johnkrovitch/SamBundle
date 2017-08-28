<?php

namespace JK\SamBundle\Command;

use Exception;
use JK\SamBundle\Watcher\Indexer\FileIndexer;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class WatchCommand extends AbstractAssetsCommand
{
    /**
     * Indicate if the watching loop should continue.
     *
     * @var bool
     */
    protected $shouldStop = false;

    /**
     *
     */
    protected function configure()
    {
        $this
            ->setName('jk:assets:watch')
            ->setDescription('Watch the assets according to your tasks configuration ("jk_assets")')
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
        $this
            ->io
            ->title('Symfony PHP Assets Manager');
        $this
            ->io
            ->section('Watch assets changes');

        $configuration = $this->loadConfiguration($input);

        // get debug mode
        $this->debug = $configuration['debug'];

        if ($this->debug) {
            $this->io->note('Debug Mode...');
        }

        // on Ctrl+C, we must stop to watch files
        pcntl_signal(SIGTERM, [$this, 'stopWatch']);
        pcntl_signal(SIGINT, [$this, 'stopWatch']);

        $indexer = new FileIndexer();
        $sources = $this->collectSources($configuration['tasks']);

        // start watching sources
        $this->io->text('Building assets...');
        $runCommand = new RunCommand();
        $runCommand->setContainer($this->container);
        $runCommand->run(new ArrayInput([]), $output);
        
        $this
            ->io
            ->text('Watching...');

        while (!$this->shouldStop) {
            $indexer->index($sources);

            if ($indexer->hasChangedEntries()) {
                $this
                    ->io
                    ->note('Sources has been modified...');

                $runCommand = new RunCommand();
                $runCommand->setContainer($this->container);
                $runCommand->run(new ArrayInput([]), $output);

                $this
                    ->io
                    ->text('Watching...');
            }

            pcntl_signal_dispatch();
            sleep(1);
        }

        // display end message
        $this->io->success('Assets watching end');

        return;
    }

    public function stopWatch()
    {
        $this->shouldStop = true;
        $this
            ->io
            ->note('Stop watching changes');
    }

    /**
     * @param array $tasks
     *
     * @return array
     */
    protected function collectSources(array $tasks)
    {
        $sources = [];
        $directories = [];
        $tasks = $this->buildTasks($tasks);

        foreach ($tasks as $task) {
            $sources = array_merge($sources, $task->getSources());
        }
        foreach ($sources as $source) {

            if (is_dir($source)) {
                $directory = $source;
            } else {
                $directory = dirname($source);
            }
            $this
                ->io
                ->text('Found new directory : '.$directory);
            $directories[] = $directory;
        }

        return $directories;
    }
}
