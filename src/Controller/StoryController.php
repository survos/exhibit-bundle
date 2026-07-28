<?php

declare(strict_types=1);

namespace Survos\ExhibitBundle\Controller;

use Survos\ExhibitBundle\Service\StoryFinder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Publishes story.v1-shaped documents over HTTP for cross-app consumers --
 * rutado/rut fetching the available tour list (see survos-sites/sos#6) is the
 * first real one, replacing what used to be a hardcoded PHP source list
 * (TourForgeSyncService::SOURCES).
 *
 * Routes are prefixed by this bundle's own route_prefix config (default
 * "/stories", see SurvosExhibitBundle::configure()) -- declared bare here so
 * the prefix isn't duplicated. Read-only: authoring is out of scope for this
 * bundle right now, see docs/authoring-openfoto-zm.md.
 */
#[AsController]
final class StoryController
{
    public function __construct(
        private readonly StoryFinder $stories,
    ) {
    }

    /**
     * List every known story, condensed to list-view fields -- no blocks/
     * resources, see show() for the full document. Small enough (a handful
     * of tours today) to return uncached and unpaginated.
     */
    #[Route('', name: 'exhibit_story_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $stories = [];
        foreach ($this->stories->findAll() as $story) {
            $stories[] = [
                'id' => $story['id'],
                'title' => $story['title'] ?? null,
                'subtitle' => $story['subtitle'] ?? null,
                'dek' => $story['dek'] ?? null,
                'blockCount' => \count($story['blocks'] ?? []),
            ];
        }

        return new JsonResponse(['stories' => $stories]);
    }

    /** The full story.v1 document -- blocks, resources, connections, projections, all of it. */
    #[Route('/{id}', name: 'exhibit_story_show', requirements: ['id' => '.+'], methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        $story = $this->stories->find($id);
        if ($story === null) {
            return new JsonResponse(['error' => \sprintf('Story "%s" not found.', $id)], 404);
        }

        return new JsonResponse($story);
    }
}
