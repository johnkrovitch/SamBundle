<?php

namespace JK\SamBundle\Watcher\Indexer;

use Exception;
use SplFileInfo;

interface FileIndexerInterface
{
    /**
     * Index a given directory : add each found files (according to the given extension) to the index.
     *
     * @param array $directories
     * @param array $extensions
     */
    public function index(array $directories, array $extensions = []);

    /**
     * Add an entry to the indexer. If a entry already exists, it will also be added to the changes.
     *
     * @param SplFileInfo $splFileInfo
     *
     * @throws Exception
     */
    public function add(SplFileInfo $splFileInfo);

    /**
     * Return an existing index entry.
     *
     * @param string $entryName
     *
     * @return SplFileInfo
     *
     * @throws Exception
     */
    public function get($entryName);

    /**
     * Return true if the entry exists, false otherwise.
     *
     * @param $entryName
     *
     * @return bool
     */
    public function has($entryName);

    /**
     * Return true if the indexer has new changes since last index.
     *
     * @return bool
     */
    public function hasChangedEntries();

    /**
     * Return the changed entry since last index.
     *
     * @return SplFileInfo[]
     */
    public function getChangedEntries();
}
