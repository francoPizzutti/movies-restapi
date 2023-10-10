<?php 

namespace App\Form\Model;

class EpisodeDto {
    /**
     * @var int[] $invitedActors
     */
    public array $invitedActors = [];
    public string $title;
    public string $summary;
    public int $episodeNumber;
    public int $directorId;
    public string $releaseDate;
}
