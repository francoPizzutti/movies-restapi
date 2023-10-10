<?php 

namespace App\Form\Model;

class SeasonDto {
    /**
     * @var EpisodeDto[] $episodes
     */
    public array $episodes = [];
    public int $seasonNumber;
    public string $title;
    public string $summary;
}