<?php

namespace App\Service;

use Exception;
use App\Entity\TVShow;
use DateTimeImmutable;
use App\Form\Model\TvShowDto;
use App\Service\ActorService;
use App\Service\SeasonService;
use App\Service\EpisodeService;
use App\Repository\ActorRepository;
use App\Exception\NotFoundException;
use App\Repository\TVShowRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use App\Exception\FailedCreationException;

class TvShowService
{
    private TVShowRepository $tvShowRepository;
    private SeasonService $seasonService;
    private EpisodeService $episodeService;
    private ActorRepository $actorRepository;
    private EntityManagerInterface $entityManager;
    private ActorService $actorService;

    public function __construct(
        TVShowRepository $tvShowRepository,
        SeasonService $seasonService,
        EpisodeService $episodeService,
        ActorService $actorService,
        ActorRepository $actorRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->tvShowRepository = $tvShowRepository;
        $this->seasonService = $seasonService;
        $this->episodeService = $episodeService;
        $this->actorService = $actorService;
        $this->actorRepository = $actorRepository;
        $this->entityManager = $entityManager;
    }

    public function createTvShow(TvShowDto $tvShowDto): TVShow
    {
        $this->actorService->validateActors($tvShowDto->actorIds);

        $tvShow = new TVShow();
        $tvShow->setTitle($tvShowDto->title);
        $tvShow->setGenre($tvShowDto->genre);
        $tvShow->setRating($tvShowDto->rating);
        $tvShow->setReleaseDate(DateTimeImmutable::createFromFormat('Y-m-d', $tvShowDto->releaseDate));
        
        $actors = $this->actorRepository->findByIdCollection($tvShowDto->actorIds);
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
            throw new FailedCreationException('TvShow');
        }

        $this->entityManager->flush();
        $this->entityManager->commit();

        return $tvShow;
    }

    public function updateTvShow(string $tvShowId, TvShowDto $tvShowDto): TVShow
    {
        $tvShow = $this->tvShowRepository->find($tvShowId);
        if(empty($tvShow)) {
            throw new NotFoundException('TvShow'); 
        }
        
        $this->actorService->validateActors($tvShowDto->actorIds);
        $this->seasonService->validateSeasons($tvShowId, $tvShowDto->seasons);

        $tvShow->setTitle($tvShowDto->title);
        $tvShow->setGenre($tvShowDto->genre);
        $tvShow->setRating($tvShowDto->rating);
        $tvShow->setReleaseDate(DateTimeImmutable::createFromFormat('Y-m-d', $tvShowDto->releaseDate));
        
        $actors = $this->actorRepository->findByIdCollection($tvShowDto->actorIds);
        foreach($actors as $actor) {
            $tvShow->addActor($actor);
        }

        $this->entityManager->beginTransaction();

        try {
            foreach($tvShowDto->seasons as $seasonData) {
                $this->seasonService->updateSeason($tvShowId, $seasonData);
            }
        } catch(Exception $e) {
            $this->entityManager->rollback();
            throw new FailedCreationException('TvShow');
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
        if(empty($tvShow)) {
            return;
        }

        $this->tvShowRepository->remove($tvShow, true);
    }

    /**
     * @return mixed[]
     */
    public function listShows(string $tvShowId = null): array
    {

        if(empty($tvShowId)) {
            $shows = $this->getShowsList();
            $result = [
                'results' => $shows,
                'total' => count($shows),
            ];

            return $result;
        }

        $show = $this->tvShowRepository->find($tvShowId);

        if(empty($show)) {
            throw new NotFoundException('TvShow');
        }

        return [
            'results' => $show->toArray(),
            'total' => 1
        ];
    }
}
