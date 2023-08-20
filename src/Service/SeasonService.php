<?php

namespace App\Service;

use App\Entity\Season;
use App\Entity\TVShow;
use App\Form\Model\SeasonDto;
use App\Repository\SeasonRepository;
use App\Service\EpisodeService;

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
}