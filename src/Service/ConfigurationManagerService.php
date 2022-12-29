<?php

namespace App\Service;

use App\Exception\VersionCorruptedException;
use App\Exception\VersionNotFoundException;
use hpeccatte\PropertiesParser\Parser;
use Symfony\Component\Filesystem\Path;

class ConfigurationManagerService
{
    private VersionManagerService $versionManager;

    private Parser $parser;

    public function __construct(VersionManagerService $versionManager)
    {
        $this->versionManager = $versionManager;
        $this->parser = new Parser();
    }

    /**
     * @throws VersionNotFoundException
     * @throws VersionCorruptedException
     */
    public function readConfigurations(string $version): array
    {
        $versionDirPath = $this->versionManager->getVersionDirPath($version);
        $serverPropertiesPath = Path::join($versionDirPath, 'server.properties');

        $serverProperties = file_get_contents($serverPropertiesPath);
        if ($serverProperties === false) {
            throw new VersionCorruptedException(sprintf('The server.properties file can not be readed. Tried %s', $serverPropertiesPath));
        }

        try {
            return $this->parser->parse($serverProperties);
        } catch (\Exception $e) {
            throw new VersionCorruptedException('The server.properties is corrupted', previous: $e);
        }
    }
}