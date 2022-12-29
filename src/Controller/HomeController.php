<?php

namespace App\Controller;

use App\Service\BehaviorPackManagerService;
use App\Service\ResourcePackManagerService;
use App\Service\VersionManagerService;
use App\Service\WorldManagerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/')]
    public function index(
        VersionManagerService $versionManager,
        WorldManagerService $worldManager,
        BehaviorPackManagerService $behaviorPackManager,
        ResourcePackManagerService $resourcePackManager
    ): Response {
        $actualVersion = $versionManager->getMainVersion();
        $versions = $versionManager->readVersions();
        $worlds = [];
        $behaviorPacks = [];
        $resourcePacks = [];

        if ($actualVersion !== null) {
            $behaviorPacks = $behaviorPackManager->readBehaviorPacks($actualVersion);
            $resourcePacks = $resourcePackManager->readResourcePacks($actualVersion);
            $worlds = $worldManager->readWorlds($actualVersion);
        }

        $actualWorld = $worldManager->getMainWorld();
        $world = null;

        if ($actualWorld !== null) {
            $world = $worldManager->readWorld($actualVersion, $actualWorld);
        }

        return $this->render('index.html.twig', [
            'actualVersion' => $actualVersion,
            'versions' => $versions,
            'worlds' => $worlds,
            'behaviorPacks' => $behaviorPacks,
            'resourcePacks' => $resourcePacks,
            'actualWorld' => $actualWorld,
            'world' => $world,
        ]);
    }
}