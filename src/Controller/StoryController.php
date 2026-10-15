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
     * List every known story -- full story.v1 documents, not condensed
     * summaries, so a consumer never needs a second per-story fetch. Real
     * story counts are small enough (a handful of tours today) that this is
     * simpler than pagination, not a shortcut around it.
     *
     * Response shape matches what survos/js-twig-bundle's DbUtilities.fetchTable()
     * (assets/src/lib/dexieDatabase.js) already expects -- bare `member`/`view.next`
     * keys (API Platform's plain JSON collection shape, not full JSON-LD/hydra:*
     * keys) -- so rut's existing <twig:dexie store="..." url="/stories"> wiring
     * can sync this endpoint with no new client code. `view.next` is always null:
     * there's no pagination yet, deliberately, until story counts stop being small.
     */
    #[Route('', name: 'exhibit_story_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $stories = \iterator_to_array($this->stories->findAll(), false);

        return new JsonResponse([
            'member' => $stories,
            'totalItems' => \count($stories),
            'view' => [
                'next' => null,
            ],
        ]);
    }

    /** The full story.v1 document -- blocks, resources, connections, projections, all of it. */
    #[Route('/{id}', name: 'exhibit_story_show', requirements: ['id' => '.+'], methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        $story = $this->stories->find($id);
        if (null === $story) {
            return new JsonResponse(['error' => \sprintf('Story "%s" not found.', $id)], 404);
        }

        return new JsonResponse($story);
    }
}
