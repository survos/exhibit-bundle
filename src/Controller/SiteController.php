<?php

declare(strict_types=1);

namespace Survos\ExhibitBundle\Controller;

use Survos\ExhibitBundle\Service\SiteFinder;
use Survos\ExhibitBundle\Service\StoryFinder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Publishes "site" metadata (branding/skin -- logo, contact, license) separately from story
 * bodies, per survos-sites/sos#6: a picker should be able to show "which sites are available"
 * without pulling every story's full content first. Routes prefixed by this bundle's own
 * route_prefix config (default "/stories" -- see SurvosExhibitBundle -- so the real paths are
 * /stories/sites, /stories/sites/{code}, /stories/sites/{code}/stories).
 */
#[AsController]
final class SiteController
{
    public function __construct(
        private readonly SiteFinder $sites,
        private readonly StoryFinder $stories,
    ) {
    }

    #[Route('/sites', name: 'exhibit_site_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $sites = \iterator_to_array($this->sites->findAll(), false);

        return new JsonResponse([
            'member' => $sites,
            'totalItems' => \count($sites),
            'view' => ['next' => null],
        ]);
    }

    #[Route('/sites/{code}', name: 'exhibit_site_show', methods: ['GET'])]
    public function show(string $code): JsonResponse
    {
        $site = $this->sites->find($code);
        if ($site === null) {
            throw new NotFoundHttpException(\sprintf('Site "%s" not found.', $code));
        }

        return new JsonResponse($site);
    }

    /** Just this site's stories -- the "pick a site, see its tours" flow, not every story everywhere. */
    #[Route('/sites/{code}/stories', name: 'exhibit_site_stories', methods: ['GET'])]
    public function stories(string $code): JsonResponse
    {
        $site = $this->sites->find($code);
        if ($site === null) {
            throw new NotFoundHttpException(\sprintf('Site "%s" not found.', $code));
        }

        $stories = \iterator_to_array($this->stories->findForDataset($site['datasetKey']), false);

        return new JsonResponse([
            'member' => $stories,
            'totalItems' => \count($stories),
            'view' => ['next' => null],
        ]);
    }
}
