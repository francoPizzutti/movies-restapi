<?php

namespace App\Service;

use Exception;
use App\Entity\TVShow;
use DateTimeImmutable;
use App\Form\Model\TvShowDto;
use App\Service\SeasonService;
use App\Service\EpisodeService;
use App\Repository\ActorRepository;
use App\Repository\TVShowRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;

class TvShowService
{
    private TVShowRepository $tvShowRepository;
    private SeasonService $seasonService;
    private EpisodeService $episodeService;
    private ActorRepository $actorRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        TVShowRepository $tvShowRepository,
        SeasonService $seasonService,
        EpisodeService $episodeService,
        ActorRepository $actorRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->tvShowRepository = $tvShowRepository;
        $this->seasonService = $seasonService;
        $this->episodeService = $episodeService;
        $this->actorRepository = $actorRepository;
        $this->entityManager = $entityManager;
    }


    /**
     * @return TVShow|array
     */
    public function createTvShow(TvShowDto $tvShowDto): TVShow
    {
        $actors = $this->actorRepository->findByIdCollection($tvShowDto->actorIds);
        
        $existingActorIds = array_map(function ($actor) {
            return $actor->getId();
        }, $actors);
        
        if(!empty(array_diff($tvShowDto->actorIds, array_values($existingActorIds)))) {
            return [
                'error' => 'Some provided actor ids weren\'t found',
                'missingActorIds' => array_values(array_diff($tvShowDto->actorIds, array_values($existingActorIds))),
            ];
        }

        $tvShow = new TVShow();
        $tvShow->setTitle($tvShowDto->title);
        $tvShow->setGenre($tvShowDto->genre);
        $tvShow->setRating($tvShowDto->rating);
        $tvShow->setReleaseDate(DateTimeImmutable::createFromFormat('Y-m-d', $tvShowDto->releaseDate));
        foreach($actors as $actor) {
            $tvShow->addActor($actor);
        }

        $this->entityManager->beginTransaction();

        try {
            $this->tvShowRepository->add($tvShow, true);

            foreach($tvShowDto->seasons as $seasonData) {
                $season = $this->seasonService->createSeason($tvShow, $seasonData);
                $tvShow->addSeason($season);
            }
        } catch(Exception $e) {
            $this->entityManager->rollback();
        }

        $this->entityManager->flush();
        $this->entityManager->commit();

        return $tvShow;
    }

    /**
     * @return mixed[] $results
     */
    public function getShowsList(): array
    {
        $shows = $this->tvShowRepository->findAll();

        $results = [];
        foreach($shows as $show) {
            $results[] = $show->toArray();
        }

        return $results;
    }

    public function removeTvShow(string $tvShowId): void
    {
        $tvShow = $this->tvShowRepository->find($tvShowId);
        if(!empty($tvShow)) {
            $this->tvShowRepository->remove($tvShow, true);
        }
    }
}
