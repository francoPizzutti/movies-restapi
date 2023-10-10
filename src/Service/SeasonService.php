<?php

namespace App\Service;

use App\Entity\Season;
use App\Entity\TVShow;
use App\Form\Model\SeasonDto;
use App\Service\EpisodeService;
use App\Exception\NotFoundException;
use App\Repository\SeasonRepository;

class SeasonService
{
    private EpisodeService $episodeService;
    private SeasonRepository $seasonRepository;

    public function __construct(
        EpisodeService $episodeService,
        SeasonRepository $seasonRepository
    ) {
        $this->episodeService = $episodeService;
        $this->seasonRepository = $seasonRepository;
    }

    public function createSeason(TVShow $tvShow, SeasonDto $seasonDto): Season
    {
        $season = new Season();
        $season->setTVShow($tvShow);
        $season->setTitle($seasonDto->title);
        $season->setSeasonNumber($seasonDto->seasonNumber);
        $season->setSummary($seasonDto->summary);

        $this->seasonRepository->add($season);

        foreach($seasonDto->episodes as $episodeData) {
            $episode = $this->episodeService->createEpisode($season, $episodeData);
            $season->addEpisode($episode);
        }

        return $season;
    }
    
    public function updateSeason(string $tvShowId, SeasonDto $seasonData): void
    {
        $season = $this->seasonRepository->findByTvShow($tvShowId, $seasonData->seasonNumber);
        $season->setTitle($seasonData->title);
        $season->setSummary($seasonData->summary);
        foreach($seasonData->episodes as $episodeData) {
            $this->episodeService->updateEpisode($season->getId(), $episodeData);
        }
    }

    /**
     * @throws NotFoundException
     */
    public function validateSeasons(string $tvShowId, array $dtoSeasons): void
    {
        if(empty($dtoSeasons)) {
            return;
        }

        foreach($dtoSeasons as $dtoSeason) {
            $season = $this->seasonRepository->findByTvShow($tvShowId, $dtoSeason->seasonNumber);
            if(empty($season)) {
                throw new NotFoundException('Season');
            }
            
            $this->episodeService->validateEpisodes($season->getId(), $dtoSeason->episodes);
        }
    }
}