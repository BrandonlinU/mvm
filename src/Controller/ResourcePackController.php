<?php

namespace App\Controller;

use App\Service\BehaviorPackManagerService;
use App\Service\ResourcePackManagerService;
use App\Util\IconUtils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ResourcePackController extends AbstractController
{
    private ResourcePackManagerService $resourcePackManager;

    public function __construct(ResourcePackManagerService $resourcePackManager)
    {
        $this->resourcePackManager = $resourcePackManager;
    }

    #[Route('/versions/{version}/resource-packs/{uuid}/icon')]
    public function getIcon(string $version, string $uuid): Response
    {
        $icon = $this->resourcePackManager->readResourcePackIcon($version, $uuid);

        return IconUtils::makeIconResponse($icon);
    }

    #[Route('/versions/{version}/resource-packs/install', methods: ['POST'])]
    public function install(Request $request, string $version): Response
    {
        /** @var UploadedFile $resourcePack */
        $resourcePack = $request->files->get('file');

        if (in_array($resourcePack->getExtension(), ['zip', 'mcpack'])) {
            $this->addFlash('danger', 'Solo se permiten archivos .zip o .mcpack');

            return $this->redirectToRoute('app_home_index');
        }
        if ($resourcePack->getMimeType() !== 'application/zip') {
            $this->addFlash('danger', 'El archivo debe ser un zip');

            return $this->redirectToRoute('app_home_index');
        }

        try {
            $this->resourcePackManager->installResourcePack($version, $resourcePack);

            $this->addFlash('success', 'Se instaló correctamente el paquete de recursos');
        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->redirectToRoute('app_home_index');
    }

    #[Route('/versions/{version}/resource-packs/{uuid}/delete')]
    public function delete(string $version, string $uuid): Response
    {
        try {
            $this->resourcePackManager->deleteResourcePack($version, $uuid);

            $this->addFlash('success', 'Se eliminó correctamente el paquete de recursos');
        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->redirectToRoute('app_home_index');
    }
}