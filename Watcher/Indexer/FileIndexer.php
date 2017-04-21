<?php

namespace JK\SamBundle\Watcher\Indexer;

use Exception;
use SplFileInfo;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class FileIndexer implements FileIndexerInterface
{
    /**
     * @var integer[]
     */
    protected $index = [];

    /**
     * @var SplFileInfo[]
     */
    protected $changes = [];

    /**
     * Index a given directory : add each found files (according to the given extension) to the index.
     *
     * @param array $directories
     * @param array $extensions
     *
     * @throws Exception
     */
    public function index(array $directories, array $extensions = [])
    {
        $fileSystem = new Filesystem();
        $finder = new Finder();

        // reset the change set
        $this->changes = [];
        clearstatcache();

        foreach ($directories as $directory) {
            if (!$fileSystem->exists($directory)) {
                throw new Exception('The directory '.$directory.' does not exists');
            }

            // find all files in the given directory
            $finder
                ->files()
                ->in($directory)
            ;

            // eventually filtering by file extension
            foreach ($extensions as $extension) {
                $finder->name('*.'.$extension);
            }

            // index the results
            foreach ($finder as $file) {
                $this->add($file);
            }
        }

    }

    /**
     * Add an entry to the indexer. If a entry already exists, it will also be added to the changes.
     *
     * @param SplFileInfo $splFileInfo
     *
     * @throws Exception
     */
    public function add(SplFileInfo $splFileInfo)
    {
        $fileSystem = new Filesystem();

        if (!$fileSystem->exists($splFileInfo->getRealPath())) {
            throw new Exception('Trying to add '.$splFileInfo->getRealPath().' missing file to the index');
        }

        // if the file is already present in the index, and its mtime has been modified, we add it to the change set
        if ($this->has($splFileInfo->getRealPath())
            && $splFileInfo->getMTime() !== $this->index[$splFileInfo->getRealPath()]) {
            $this->changes[$splFileInfo->getRealPath()] = $splFileInfo;
        }
        // add new or existing file to the index
        $this->index[$splFileInfo->getRealPath()] = $splFileInfo->getMTime();
    }

    /**
     * Return an existing index entry.
     *
     * @param string $entryName
     *
     * @return SplFileInfo
     *
     * @throws Exception
     */
    public function get($entryName)
    {
        if (!$this->has($entryName)) {
            throw new Exception('Trying to get invalid index entry : '.$entryName);
        }

        return new SplFileInfo($entryName);
    }

    /**
     * Return true if the entry exists, false otherwise.
     *
     * @param $entryName
     *
     * @return bool
     */
    public function has($entryName)
    {
        return array_key_exists($entryName, $this->index);
    }

    /**
     * Return true if the indexer has new changes since last index.
     *
     * @return bool
     */
    public function hasChangedEntries()
    {
        return count($this->changes) > 0;
    }

    /**
     * Return the changed entry since last index.
     *
     * @return SplFileInfo[]
     */
    public function getChangedEntries()
    {
        return $this->changes;
    }
}
