<?php

namespace App\Service;

use App\Exception\BehaviorPackCorruptedException;
use App\Exception\ContentPackCorruptedException;
use App\Exception\VersionNotFoundException;
use App\Model\ContentPack;
use App\Model\IconContentPack;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class BehaviorPackManagerService extends ContentPackManagerService
{
    private VersionManagerService $versionManager;

    private static array $behaviorPacks;

    public function __construct(VersionManagerService $versionManager, Filesystem $filesystem)
    {
        parent::__construct($filesystem);

        $this->versionManager = $versionManager;
    }

    /**
     * @throws VersionNotFoundException
     * @throws BehaviorPackCorruptedException
     */
    public function readBehaviorPacks(string $version): array
    {
        if (isset(static::$behaviorPacks[$version])) {
            return static::$behaviorPacks[$version];
        }

        $versionDirPath = $this->versionManager->getVersionDirPath($version);
        $behaviorPacksPath = Path::join($versionDirPath, 'behavior_packs');

        try {
            return static::$behaviorPacks[$version] = $this->readContentPacks($behaviorPacksPath);
        } catch (ContentPackCorruptedException $e) {
            throw new BehaviorPackCorruptedException('The behavior pack is corrupted', previous: $e);
        }
    }

    public function installBehaviorPack(string $version, \SplFileInfo $behaviorPack): void
    {
        $versionDirPath = $this->versionManager->getVersionDirPath($version);
        $behaviorPacksPath = Path::join($versionDirPath, 'behavior_packs');

        $this->installContentPack($behaviorPacksPath, $behaviorPack);
    }

    public function getBehaviorPack(string $version, string $uuid): ContentPack
    {
        $behaviorPacks = $this->readBehaviorPacks($version);

        return $behaviorPacks[$uuid];
    }

    public function deleteBehaviorPack(string $version, string $uuid): void
    {
        $behaviorPack = $this->getBehaviorPack($version, $uuid);

        $this->deleteContentPack($behaviorPack->getPath());
    }

    /**
     * @throws VersionNotFoundException
     * @throws BehaviorPackCorruptedException
     */
    public function readBehaviorPackIcon(string $version, string $uuid): IconContentPack
    {
        $behaviorPacks = $this->readBehaviorPacks($version);
        $behaviorPackPath = $behaviorPacks[$uuid]->getPath();

        try {
            return $this->readContentPackIcon($behaviorPackPath);
        } catch (ContentPackCorruptedException $e) {
            throw new BehaviorPackCorruptedException('The behaviour pack icon is corrupted', previous: $e);
        }
    }
}