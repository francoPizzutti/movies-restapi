<?php

namespace App\Service;

use Exception;
use App\Entity\Season;
use DateTimeImmutable;
use App\Entity\Episode;
use App\Service\ActorService;
use App\Form\Model\EpisodeDto;
use App\Service\DirectorService;
use App\Repository\ActorRepository;
use App\Exception\NotFoundException;
use App\Repository\EpisodeRepository;
use App\Repository\DirectorRepository;

class EpisodeService
{
    private DirectorRepository $directorRepository;
    private ActorRepository $actorRepository;
    private EpisodeRepository $episodeRepository;
    private ActorService $actorService;
    private DirectorService $directorService;
    
    public function __construct(
        DirectorRepository $directorRepository,
        ActorRepository $actorRepository,
        EpisodeRepository $episodeRepository,
        ActorService $actorService,
        DirectorService $directorService
    ) {
        $this->directorRepository = $directorRepository;
        $this->actorRepository = $actorRepository;
        $this->episodeRepository = $episodeRepository;
        $this->actorService = $actorService;
        $this->directorService = $directorService;
    }

    public function createEpisode(Season $season, EpisodeDto $episodeDto): Episode
    {
        $episode = new Episode();
        $episode->setSeason($season);
        $episode->setTitle($episodeDto->title);
        $episode->setEpisodeSummary($episodeDto->summary);
        $episode->setEpisodeNumber($episodeDto->episodeNumber);
        $episode->setReleaseDate(DateTimeImmutable::createFromFormat('Y-m-d', $episodeDto->releaseDate));

        $director = $this->directorRepository->find($episodeDto->directorId);

        if(empty($director)) {
            throw new Exception('Wrong director ID provided for episode ' . $episode->getTitle());
        }
        
        $episode->setDirector($director);

        $actors = $this->actorRepository->findByIdCollection($episodeDto->invitedActors);
        $this->updateEpisodeActors($episode, $episodeDto, $actors);

        $this->episodeRepository->add($episode);

        return $episode;
    }

    public function updateEpisodeActors(Episode &$episode, EpisodeDto $episodeDto, array $newActors): void
    {
        foreach ($episode->getInvitedActors() as $previousActor) {
            if(!in_array($previousActor->getId(), $episodeDto->invitedActors)) {
                $episode->removeInvitedActor($previousActor);
            }
        }

        foreach($newActors as $actor) {
            $episode->addInvitedActor($actor);
        }
    }

    public function updateEpisode(string $seasonId, EpisodeDto $episodeData): void
    {
        $episode = $this->episodeRepository->findBySeasonId($seasonId, $episodeData->episodeNumber);
        $episode->setTitle($episodeData->title);
        $episode->setEpisodeSummary($episodeData->summary);
        $episode->setDirector($this->directorRepository->find($episodeData->directorId));
        
        $newActors = $this->actorRepository->findByIdCollection($episodeData->invitedActors);
        foreach($episode->getInvitedActors() as $invitedActor) {
            if(!in_array($invitedActor->getId(), $episodeData->invitedActors)) {
                $episode->removeInvitedActor($invitedActor);
            }
        }

        foreach($newActors as $newActor) {
            $episode->addInvitedActor($newActor);
        }
    }

    /**
     * @throws NotFoundException
     */
    public function validateEpisodes(string $seasonId, array $dtoEpisodes): void
    {
        if(empty($dtoEpisodes)) {
            return;
        }

        foreach($dtoEpisodes as $dtoEpisode) {
            $episode = $this->episodeRepository->findBySeasonId($seasonId, $dtoEpisode->episodeNumber);
            if(empty($episode)) {
                throw new NotFoundException('Episode');
            }

            $this->actorService->validateActors($dtoEpisode->invitedActors);
            $this->directorService->validateDirector($dtoEpisode->directorId);
        }
    }
}