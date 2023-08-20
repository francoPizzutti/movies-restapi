<?php 

namespace App\Form\Model;

class TvShowDto {
    /**
     * @var int[] $actorIds
     */
    public array $actorIds;

    /**
     * @var SeasonDto[] $seasons
     */
    public array $seasons;

    public string $title;
    public string $genre;
    public string $releaseDate;
    public ?float $rating;
    
}