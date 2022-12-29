<?php

namespace App\Controller;

use App\Service\WorldManagerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class WorldController extends AbstractController
{
    private WorldManagerService $worldManager;

    public function __construct(WorldManagerService $worldManager)
    {
        $this->worldManager = $worldManager;
    }

    #[Route('/versions/{version}/worlds/{world}/behavior-packs/{uuid}/activate')]
    public function activateBehaviorPack(Request $request, string $version, string $world, string $uuid): Response
    {
        $csrfToken = $request->request->get('token');

        if (!$this->isCsrfTokenValid("activate-behavior-$version-$world-$uuid", $csrfToken)) {
            $this->addFlash('danger', 'El token csrf es inválido, por favor, intente de nuevo');

            return $this->redirectToRoute('app_home_index');
        }

        try {
            $this->worldManager->activateBehaviorPack($version, $world, $uuid);

            $this->addFlash('success', 'Se activó correctamente el paquete de comportamiento');
        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->redirectToRoute('app_home_index');
    }

    #[Route('/versions/{version}/worlds/{world}/behavior-packs/{uuid}/deactivate')]
    public function deactivateBehaviorPack(Request $request, string $version, string $world, string $uuid): Response
    {
        $csrfToken = $request->request->get('token');

        if (!$this->isCsrfTokenValid("deactivate-behavior-$version-$world-$uuid", $csrfToken)) {
            $this->addFlash('danger', 'El token csrf es inválido, por favor, intente de nuevo');

            return $this->redirectToRoute('app_home_index');
        }

        try {
            $this->worldManager->deactivateBehaviorPack($version, $world, $uuid);

            $this->addFlash('success', 'Se desactivó correctamente el paquete de comportamiento');
        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->redirectToRoute('app_home_index');
    }

    #[Route('/versions/{version}/worlds/{world}/resource-packs/{uuid}/activate')]
    public function activateResourcePack(Request $request, string $version, string $world, string $uuid): Response
    {
        $csrfToken = $request->request->get('token');

        if (!$this->isCsrfTokenValid("activate-resource-$version-$world-$uuid", $csrfToken)) {
            $this->addFlash('danger', 'El token csrf es inválido, por favor, intente de nuevo');

            return $this->redirectToRoute('app_home_index');
        }

        try {
            $this->worldManager->activateResourcePack($version, $world, $uuid);

            $this->addFlash('success', 'Se activó correctamente el paquete de comportamiento');
        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->redirectToRoute('app_home_index');
    }

    #[Route('/versions/{version}/worlds/{world}/resource-packs/{uuid}/deactivate')]
    public function deactivateResourcePack(Request $request, string $version, string $world, string $uuid): Response
    {
        $csrfToken = $request->request->get('token');

        if (!$this->isCsrfTokenValid("deactivate-resource-$version-$world-$uuid", $csrfToken)) {
            $this->addFlash('danger', 'El token csrf es inválido, por favor, intente de nuevo');

            return $this->redirectToRoute('app_home_index');
        }

        try {
            $this->worldManager->deactivateResourcePack($version, $world, $uuid);

            $this->addFlash('success', 'Se desactivó correctamente el paquete de recursos');
        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->redirectToRoute('app_home_index');
    }
}