<?php

namespace App\Service;

use App\Exception\BehaviorPackCorruptedException;
use App\Exception\ResourcePackCorruptedException;
use App\Exception\VersionCorruptedException;
use App\Exception\VersionNotFoundException;
use App\Exception\WorldCorruptedException;
use App\Exception\WorldNotFoundException;
use App\Model\World;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;

class WorldManagerService
{
    private VersionManagerService $versionManager;

    private ConfigurationManagerService $configurationManager;

    private BehaviorPackManagerService $behaviorPackManager;

    private ResourcePackManagerService $resourcePackManager;

    private Filesystem $filesystem;

    private static array $worlds;

    public function __construct(
        VersionManagerService $versionManager,
        ConfigurationManagerService $configurationManager,
        BehaviorPackManagerService $behaviorPackManager,
        ResourcePackManagerService $resourcePackManager,
        Filesystem $filesystem
    ) {
        $this->versionManager = $versionManager;
        $this->configurationManager = $configurationManager;
        $this->behaviorPackManager = $behaviorPackManager;
        $this->resourcePackManager = $resourcePackManager;
        $this->filesystem = $filesystem;
    }

    /**
     * @throws VersionCorruptedException
     * @throws VersionNotFoundException
     */
    public function getMainWorld(): ?string
    {
        $mainVersion = $this->versionManager->getMainVersion();
        if ($mainVersion === null) {
            return null;
        }

        $configurations = $this->configurationManager->readConfigurations($mainVersion);

        return $configurations['level-name'];
    }

    /**
     * @throws ResourcePackCorruptedException
     * @throws WorldCorruptedException
     * @throws WorldNotFoundException
     * @throws BehaviorPackCorruptedException
     * @throws VersionNotFoundException
     */
    public function readWorld(string $version, string $name): World
    {
        $worlds = $this->readWorlds($version);

        if (!isset($worlds[$name])) {
            throw new WorldNotFoundException(sprintf('The world %s does not exists', $name));
        }

        return $worlds[$name];
    }

    /**
     * @throws BehaviorPackCorruptedException
     * @throws ResourcePackCorruptedException
     * @throws VersionNotFoundException
     * @throws WorldCorruptedException
     */
    public function readWorlds(string $version): array
    {
        if (isset(static::$worlds[$version])) {
            return static::$worlds[$version];
        }

        $versionDirPath = $this->versionManager->getVersionDirPath($version);
        $worldsPath = Path::join($versionDirPath, 'worlds');
        $worldDirs = (new Finder())
            ->directories()
            ->in($worldsPath)
            ->depth(0)
        ;

        $behaviorPacks = $this->behaviorPackManager->readBehaviorPacks($version);
        $resourcePacks = $this->resourcePackManager->readResourcePacks($version);
        $worlds = [];
        /** @var \SplFileInfo $worldDir */
        foreach ($worldDirs as $worldDir) {
            $worldPath = $worldDir->getPathname();
            $levelNamePath = Path::join($worldPath, 'levelname.txt');

            $worldName = file_get_contents($levelNamePath);
            if ($worldName === false) {
                throw new WorldCorruptedException(sprintf('The world levelname file can not be read. Tried %s', $levelNamePath));
            }

            $worldBehaviorPackManifestPath = Path::join($worldPath, 'world_behavior_packs.json');
            $worldBehaviorPacks = $this->readWorldContentPackManifest($behaviorPacks, $worldBehaviorPackManifestPath);

            $worldResourcePacksPath = Path::join($worldPath, 'world_resource_packs.json');
            $worldResourcePacks = $this->readWorldContentPackManifest($resourcePacks, $worldResourcePacksPath);

            $worlds[$worldName] = new World($worldName, $worldPath, $worldBehaviorPacks, $worldResourcePacks);
        }

        return static::$worlds[$version] = $worlds;
    }

    public function activateBehaviorPack(string $version, string $worldName, string $uuid): void
    {
        $world = $this->readWorld($version, $worldName);
        $worldPath = $world->getPath();

        $behaviorPack = $this->behaviorPackManager->getBehaviorPack($version, $uuid);
        $world->addBehaviorPack($behaviorPack);

        $behaviorPacks = $world->getBehaviourPacks();
        $manifestPath = Path::join($worldPath, 'world_behavior_packs.json');

        $this->writeWorldContentPackManifest($behaviorPacks, $manifestPath);
    }

    public function deactivateBehaviorPack(string $version, string $worldName, string $uuid): void
    {
        $world = $this->readWorld($version, $worldName);
        $worldPath = $world->getPath();

        $behaviorPack = $this->behaviorPackManager->getBehaviorPack($version, $uuid);
        $world->removeBehaviorPack($behaviorPack);

        $behaviorPacks = $world->getBehaviourPacks();
        $manifestPath = Path::join($worldPath, 'world_behavior_packs.json');

        $this->writeWorldContentPackManifest($behaviorPacks, $manifestPath);
    }

    public function activateResourcePack(string $version, string $worldName, string $uuid): void
    {
        $world = $this->readWorld($version, $worldName);
        $worldPath = $world->getPath();

        $resourcePack = $this->resourcePackManager->getResourcePack($version, $uuid);
        $world->addResourcePack($resourcePack);

        $resourcePacks = $world->getResourcePacks();
        $manifestPath = Path::join($worldPath, 'world_resource_packs.json');

        $this->writeWorldContentPackManifest($resourcePacks, $manifestPath);
    }

    public function deactivateResourcePack(string $version, string $worldName, string $uuid): void
    {
        $world = $this->readWorld($version, $worldName);
        $worldPath = $world->getPath();

        $resourcePack = $this->resourcePackManager->getResourcePack($version, $uuid);
        $world->removeResourcePack($resourcePack);

        $resourcePacks = $world->getResourcePacks();
        $manifestPath = Path::join($worldPath, 'world_resource_packs.json');

        $this->writeWorldContentPackManifest($resourcePacks, $manifestPath);
    }

    private function readWorldContentPackManifest(array $availableContentPacks, string $worldContentPackManifestPath): array
    {
        $worldContentPacks = [];
        if (!$this->filesystem->exists($worldContentPackManifestPath)) {
            return $worldContentPacks;
        }

        $worldContentPacksJson = file_get_contents($worldContentPackManifestPath);
        if ($worldContentPacksJson !== false) {
            $manifest = json_decode($worldContentPacksJson, true, 512, JSON_THROW_ON_ERROR);

            foreach ($manifest as $contentPack) {
                $packId = $contentPack['pack_id'];
                $version = $contentPack['version'];

                if (isset($availableContentPacks[$packId])) {
                    $worldContentPacks[$packId] = $availableContentPacks[$packId];
                }
            }
        }

        return $worldContentPacks;
    }

    private function writeWorldContentPackManifest(array $enabledContentPacks, string $worldContentPackManifestPath): void
    {
        $contentPacks = [];
        foreach ($enabledContentPacks as $contentPack) {
            $contentPacks[] = [
                'pack_id' => $contentPack->getUuid(),
                'version' => $contentPack->getVersion(),
            ];
        }

        $worldContentPackManifestJson = json_encode($contentPacks, JSON_THROW_ON_ERROR);

        file_put_contents($worldContentPackManifestPath, $worldContentPackManifestJson);
    }
}