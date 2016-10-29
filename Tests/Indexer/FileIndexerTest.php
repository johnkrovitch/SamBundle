<?php

namespace JK\SamBundle\Tests\Watcher;

use Exception;
use JK\SamBundle\Watcher\Indexer\FileIndexer;
use PHPUnit_Framework_TestCase;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

class FileIndexerTest extends PHPUnit_Framework_TestCase
{
    /**
     * An invalid directory should throw an Exception.
     */
    public function testIndexException()
    {
        $indexer = new FileIndexer();
        $exceptionRaised = false;

        try {
            $indexer->index(__DIR__.'/assets');
        } catch (Exception $exception) {
            $exceptionRaised = true;
        }
        $this->assertTrue($exceptionRaised);
    }

    /**
     * The files in the given directory to index should be added to the index during indexing.
     */
    public function testIndex()
    {
        $indexer = new FileIndexer();
        $indexer->index($this->getAssetsDirectory());
        $finder = new Finder();
        $finder
            ->files()
            ->in($this->getAssetsDirectory())
        ;

        foreach ($finder as $fileInfo) {
            $this->assertTrue($indexer->has($fileInfo->getRealPath()));
            $this->assertInstanceOf(SplFileInfo::class, $indexer->get($fileInfo->getRealPath()));
        }
    }

    /**
     * The files in the given directory to index should be added to the index during indexing.
     */
    public function testIndexFilteredByExtensions()
    {
        $indexer = new FileIndexer();
        $indexer->index($this->getAssetsDirectory(), ['txt']);
        $finder = new Finder();
        $finder
            ->files()
            ->in($this->getAssetsDirectory())
            ->name('*.txt')
        ;

        foreach ($finder as $fileInfo) {
            $this->assertTrue($indexer->has($fileInfo->getRealPath()));
            $this->assertInstanceOf(SplFileInfo::class, $indexer->get($fileInfo->getRealPath()));
        }
    }

    /**
     * Adding a invalid file should throw an exception.
     */
    public function testAddException()
    {
        $indexer = new FileIndexer();
        $exceptionRaised = false;

        try {
            $indexer->add(new SplFileInfo('/some_path/is_wrong'));
        } catch (Exception $exception) {
            $exceptionRaised = true;
        }
        $this->assertTrue($exceptionRaised);
    }

    /**
     * Adding a invalid file should throw an exception.
     */
    public function testGetException()
    {
        $indexer = new FileIndexer();
        $exceptionRaised = false;

        try {
            $indexer->get('/some_path/is_wrong');
        } catch (Exception $exception) {
            $exceptionRaised = true;
        }
        $this->assertTrue($exceptionRaised);
    }

    /**
     * The reindex should only put in the change set the modified files.
     */
    public function testReIndex()
    {
        $modifiedFile = $this->getAssetsDirectory().'/test.scss';
        $indexer = new FileIndexer();

        // index for the first time
        $indexer->index($this->getAssetsDirectory(), ['scss']);

        // modify a file
        touch($modifiedFile);

        // reindex
        $indexer->index($this->getAssetsDirectory());

        // one file must be found in the change set
        $this->assertCount(1, $indexer->getChangedEntries());
        $this->assertTrue($indexer->hasChangedEntries());
        $this->assertEquals($modifiedFile, $indexer->get($modifiedFile)->getRealPath());

    }

    protected function getAssetsDirectory()
    {
        return realpath(__DIR__.'/../fixtures/assets');
    }
}
