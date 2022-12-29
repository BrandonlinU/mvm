<?php

namespace App\Controller;

use App\Service\BehaviorPackManagerService;
use App\Util\IconUtils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BehaviorPackController extends AbstractController
{
    private BehaviorPackManagerService $behaviorPackManager;

    public function __construct(BehaviorPackManagerService $behaviorPackManager)
    {
        $this->behaviorPackManager = $behaviorPackManager;
    }

    #[Route('/versions/{version}/behavior-packs/{uuid}/icon', methods: ['GET'])]
    public function getIcon(string $version, string $uuid): Response
    {
        $icon = $this->behaviorPackManager->readBehaviorPackIcon($version, $uuid);

        return IconUtils::makeIconResponse($icon);
    }

    #[Route('/versions/{version}/behavior-packs/install', methods: ['POST'])]
    public function install(Request $request, string $version): Response
    {
        /** @var UploadedFile $behaviorPack */
        $behaviorPack = $request->files->get('file');

        if (in_array($behaviorPack->getClientOriginalExtension(), ['zip', 'mcpack'])) {
            $this->addFlash('danger', 'Solo se permiten archivos .zip o .mcpack');

            return $this->redirectToRoute('app_home_index');
        }
        if ($behaviorPack->getMimeType() !== 'application/zip') {
            $this->addFlash('danger', 'El archivo debe ser un zip');

            return $this->redirectToRoute('app_home_index');
        }

        try {
            $this->behaviorPackManager->installBehaviorPack($version, $behaviorPack);

            $this->addFlash('success', 'Se instaló correctamente el paquete de comportamiento');
        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->redirectToRoute('app_home_index');
    }

    #[Route('/versions/{version}/behavior-packs/{uuid}/delete', methods: ['POST'])]
    public function delete(string $version, string $uuid): Response
    {
        try {
            $this->behaviorPackManager->deleteBehaviorPack($version, $uuid);

            $this->addFlash('success', 'Se eliminó correctamente el paquete de comportamiento');
        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->redirectToRoute('app_home_index');
    }
}