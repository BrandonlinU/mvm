<?php

namespace App\Service;

use App\Exception\ContentPackCorruptedException;
use App\Model\ContentPack;
use App\Model\IconContentPack;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\String\Slugger\AsciiSlugger;

abstract class ContentPackManagerService
{
    protected Filesystem $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    protected function readContentPacks(string $contentPacksPath): array
    {
        $contentPackDirs = (new Finder())
            ->directories()
            ->in($contentPacksPath)
            ->depth(0)
        ;

        $contentPacks = [];
        /** @var \SplFileInfo $contentPackDir */
        foreach ($contentPackDirs as $contentPackDir) {
            $contentPackPath = $contentPackDir->getPathname();
            $manifestPath = Path::join($contentPackPath, 'manifest.json');

            $manifestJson = file_get_contents($manifestPath);
            if ($manifestJson === false) {
                throw new ContentPackCorruptedException(sprintf('The manifest file can not be read. Tried %s', $manifestPath));
            }

            try {
                $saneJson = $this->stripComments($manifestJson);
                $manifest = json_decode($saneJson, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                throw new ContentPackCorruptedException('The manifest file can not be decoded', previous: $e);
            }

            $uuid = $manifest['header']['uuid'];
            $name = $manifest['header']['name'];
            $description = $manifest['header']['description'];
            $version = $manifest['header']['version'];

            // Skip internal content packs
            if (
                str_contains($name, 'vanilla') ||
                str_contains($name, 'Experimental')
            ) {
                continue;
            }

            $contentPacks[$uuid] = new ContentPack($contentPackPath, $uuid, $name, $description, $version);
        }

        return $contentPacks;
    }

    /**
     * @throws ContentPackCorruptedException
     */
    protected function installContentPack(string $contentPacksPath, \SplFileInfo $contentPack): void
    {
        $contentPackPath = $contentPack->getPathname();
        $contentPackZip = new \ZipArchive();
        $contentPackZip->open($contentPackPath, \ZipArchive::RDONLY);

        if($contentPackZip->locateName('manifest.json')) {
            $this->installMcPack($contentPacksPath, $contentPackZip);
        } else {
            $this->installZipPack($contentPacksPath, $contentPackZip);
        }

        $contentPackZip->close();
        $this->filesystem->remove($contentPackPath);
    }

    protected function deleteContentPack(string $contentPackPath): void
    {
        if (!$this->filesystem->exists($contentPackPath)) {
            throw new ContentPackCorruptedException('The content pack is not installed in the server');
        }

        $this->filesystem->remove($contentPackPath);
    }

    protected function readContentPackIcon(string $contentPackPath): IconContentPack
    {
        $iconPath = Path::join($contentPackPath, 'pack_icon.png');

        $icon = file_get_contents($iconPath);
        if ($icon === false) {
            throw new ContentPackCorruptedException('The icon file can not be decoded');
        }

        return new IconContentPack($icon, 'image/png');
    }

    /**
     * @throws ContentPackCorruptedException
     */
    private function decodeManifest(string $json): array
    {
        try {
            $saneJson = $this->stripComments($json);
            $manifest = json_decode($saneJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new ContentPackCorruptedException('The manifest file can not be decoded', previous: $e);
        }

        return $manifest;
    }

    private function stripComments(string $json): string
    {
        return preg_replace('#[ \t]*//.*[ \t]*[\r\n]#', '', $json);
    }

    /**
     * @throws ContentPackCorruptedException
     */
    private function installMcPack(string $contentPacksPath, \ZipArchive $mcPack): void
    {
        $manifestIndex = $mcPack->locateName('manifest.json');
        $manifestJson = $mcPack->getFromIndex($manifestIndex);
        $manifest = $this->decodeManifest($manifestJson);

        $slugger = new AsciiSlugger();
        $name = $manifest['header']['name'];

        $contentPackDir = $slugger->slug($name);
        $contentPackPath = Path::join($contentPacksPath, $contentPackDir);

        if (!$this->filesystem->exists($contentPackPath)) {
            $this->filesystem->mkdir($contentPackPath);
        }

        $mcPack->extractTo($contentPackPath);
    }

    private function installZipPack(string $contentPacksPath, \ZipArchive $zipPack): void
    {
        $zipPack->extractTo($contentPacksPath);
    }
}