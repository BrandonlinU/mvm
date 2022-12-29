<?php

namespace App\Service;

use App\Exception\DownloadFailedException;
use App\Exception\VersionExistsException;
use App\Exception\VersionNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class VersionManagerService
{
    private HttpClientInterface $httpClient;

    private Filesystem $filesystem;

    private string $serverDir;

    private string $serverLinkPath;

    public function __construct(HttpClientInterface $httpClient, Filesystem $filesystem, string $serverDir)
    {
        $this->httpClient = $httpClient;
        $this->filesystem = $filesystem;
        $this->serverDir = $serverDir;
        $this->serverLinkPath = Path::join($serverDir, 'main');
    }

    public function getMainVersion(): ?string
    {
        $serverPath = $this->filesystem->readlink($this->serverLinkPath, true);
        if ($serverPath === null) {
            return null;
        }

        return basename($serverPath);
    }

    public function readVersions(): array
    {
        $versionDirs = (new Finder())
            ->directories()
            ->in($this->serverDir)
            ->notName('main')
            ->depth(0)
        ;
        $versions = [];

        /** @var \SplFileInfo $versionDir */
        foreach ($versionDirs as $versionDir) {
            $versions[] = $versionDir->getFilename();
        }

        return $versions;
    }

    /**
     * @throws VersionNotFoundException
     */
    public function checkVersion(string $version): bool
    {
        return $this->filesystem->exists($this->getVersionDirPath($version, false));
    }

    /**
     * @throws VersionNotFoundException
     */
    public function activateVersion(string $version): void
    {
        $this->filesystem->symlink($this->getVersionDirPath($version), $this->serverLinkPath);
    }

    /**
     * @throws VersionNotFoundException
     */
    public function deleteVersion(string $version): void
    {
        $this->filesystem->remove($this->getVersionDirPath($version));
    }

    /**
     * @throws VersionNotFoundException
     * @throws DownloadFailedException
     * @throws VersionExistsException
     */
    public function downloadVersion(string $version, bool $force = false): void
    {
        if (!$force && $this->checkVersion($version)) {
            throw new VersionExistsException(sprintf('The version %s is installed in the server', $version));
        }

        try {
            $response = $this->httpClient->request(
                'GET',
                sprintf('https://minecraft.azureedge.net/bin-linux/bedrock-server-%s.zip', $version),
            );

            $tempFilePath = $this->filesystem->tempnam(sys_get_temp_dir(), sprintf('mc-%s', $version));
            $versionFile = fopen($tempFilePath, 'wb');
            foreach ($this->httpClient->stream($response) as $chunk) {
                fwrite($versionFile, $chunk->getContent());
            }
            fclose($versionFile);
        } catch (ClientExceptionInterface $e) {
            $response = $e->getResponse();
            if ($response->getStatusCode() === 404) {
                throw new VersionNotFoundException(sprintf('The version %s does not exists', $version), previous: $e);
            }

            throw new DownloadFailedException(sprintf('The version %s can not be downloaded', $version), previous: $e);
        } catch (TransportExceptionInterface $e) {
            throw new DownloadFailedException(sprintf('The version %s can not be downloaded', $version), previous: $e);
        }

        $versionDirPath = $this->getVersionDirPath($version, false);
        $this->filesystem->remove($versionDirPath);

        $versionZipFile = new \ZipArchive();
        if (true !== $versionZipFile->open($tempFilePath, \ZipArchive::RDONLY)) {
            throw new DownloadFailedException(sprintf('The version %s can not be unzipped', $version));
        }

        if (true !== $versionZipFile->extractTo($versionDirPath)) {
            throw new DownloadFailedException(sprintf('The version %s can not be unzipped', $version));
        }
        $versionZipFile->close();

        $this->filesystem->remove($tempFilePath);
    }

    public function getVersionDirPath(string $version, bool $checkIfExists = true): string
    {
        $versionDirPath = Path::join($this->serverDir, $version);

        if ($checkIfExists && !$this->filesystem->exists($versionDirPath)) {
            throw new VersionNotFoundException(sprintf('The version %s is not installed in the server', $version));
        }

        return $versionDirPath;
    }
}