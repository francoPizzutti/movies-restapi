<?php

namespace App\Service;

use Exception;
use App\Entity\Season;
use DateTimeImmutable;
use App\Entity\Episode;
use App\Form\Model\EpisodeDto;
use App\Repository\ActorRepository;
use App\Repository\EpisodeRepository;
use App\Repository\DirectorRepository;

class EpisodeService
{
    private DirectorRepository $directorRepository;
    private ActorRepository $actorRepository;
    private EpisodeRepository $episodeRepository;
    
    public function __construct(
        DirectorRepository $directorRepository,
        ActorRepository $actorRepository,
        EpisodeRepository $episodeRepository
    ) {
        $this->directorRepository = $directorRepository;
        $this->actorRepository = $actorRepository;
        $this->episodeRepository = $episodeRepository;
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
}