<?php 

namespace App\Form\Model;

class MovieDto {
    /**
     * @var int[] $actorIds
     */
    public array $actorIds;
    public string $name;
    public string $genre;
    public int $duration;
    public int $directorId;


}