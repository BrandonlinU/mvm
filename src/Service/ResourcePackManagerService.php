<?php

namespace App\Service;

use App\Exception\ContentPackCorruptedException;
use App\Exception\ResourcePackCorruptedException;
use App\Exception\VersionNotFoundException;
use App\Model\ContentPack;
use App\Model\IconContentPack;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class ResourcePackManagerService extends ContentPackManagerService
{
    private VersionManagerService $versionManager;

    private static array $resourcePacks;

    public function __construct(VersionManagerService $versionManager, Filesystem $filesystem)
    {
        parent::__construct($filesystem);

        $this->versionManager = $versionManager;
    }

    /**
     * @throws VersionNotFoundException
     * @throws ResourcePackCorruptedException
     */
    public function readResourcePacks(string $version): array
    {
        if (isset(static::$resourcePacks[$version])) {
            return static::$resourcePacks[$version];
        }

        $versionDirPath = $this->versionManager->getVersionDirPath($version);
        $resourcePacksPath = Path::join($versionDirPath, 'resource_packs');

        try {
            return static::$resourcePacks[$version] = $this->readContentPacks($resourcePacksPath);
        } catch (ContentPackCorruptedException $e) {
            throw new ResourcePackCorruptedException('The resource pack is corrupted', previous: $e);
        }
    }

    public function installResourcePack(string $version, \SplFileInfo $resourcePack): void
    {
        $versionDirPath = $this->versionManager->getVersionDirPath($version);
        $resourcePacksPath = Path::join($versionDirPath, 'resource_packs');

        $this->installContentPack($resourcePacksPath, $resourcePack);
    }

    public function getResourcePack(string $version, string $uuid): ContentPack
    {
        $resourcePacks = $this->readResourcePacks($version);

        return $resourcePacks[$uuid];
    }

    public function deleteResourcePack(string $version, string $uuid): void
    {
        $resourcePack = $this->getResourcePack($version, $uuid);

        $this->deleteContentPack($resourcePack->getPath());
    }

    /**
     * @throws VersionNotFoundException
     * @throws ResourcePackCorruptedException
     */
    public function readResourcePackIcon(string $version, string $uuid): IconContentPack
    {
        $resourcePacks = $this->readResourcePacks($version);
        $contentPackPath = $resourcePacks[$uuid]->getPath();

        try {
            return $this->readContentPackIcon($contentPackPath);
        } catch (ContentPackCorruptedException $e) {
            throw new ResourcePackCorruptedException('The resource pack icon is corrupted', previous: $e);
        }
    }
}