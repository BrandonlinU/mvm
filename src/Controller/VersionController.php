<?php

namespace App\Controller;

use App\Service\VersionManagerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class VersionController extends AbstractController
{
    private VersionManagerService $versionManager;

    public function __construct(VersionManagerService $versionManager)
    {
        $this->versionManager = $versionManager;
    }

    #[Route('/versions/download', methods: ['POST'])]
    public function download(Request $request): Response
    {
        $version = trim($request->request->get('version'));
        $force = $request->request->getBoolean('force');

        try {
            $this->versionManager->downloadVersion($version, $force);

            $this->addFlash('success', 'Se descargó correctamente la actualización');
        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->redirectToRoute('app_home_index');
    }

    #[Route('/versions/{version}/activate', methods: ['POST'])]
    public function activate(Request $request, string $version): Response
    {
        $csrfToken = $request->request->get('token');

        if (!$this->isCsrfTokenValid("activate-$version", $csrfToken)) {
            $this->addFlash('danger', 'El token csrf es inválido, por favor, intente de nuevo');

            return $this->redirectToRoute('app_home_index');
        }

        try {
            $this->versionManager->activateVersion($version);

            $this->addFlash('success', 'Se activó correctamente la versión de Minecraft');
        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->redirectToRoute('app_home_index');
    }

    #[Route('/versions/{version}/delete', methods: ['POST'])]
    public function delete(Request $request, string $version): Response
    {
        $csrfToken = $request->request->get('token');

        if (!$this->isCsrfTokenValid("delete-$version", $csrfToken)) {
            $this->addFlash('danger', 'El token csrf es inválido, por favor, intente de nuevo');

            return $this->redirectToRoute('app_home_index');
        }

        try {
            $this->versionManager->deleteVersion($version);

            $this->addFlash('success', 'Se eliminó correctamente la versión de Minecraft');
        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->redirectToRoute('app_home_index');
    }
}