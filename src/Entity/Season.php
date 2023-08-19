<?php

namespace App\Entity;

use App\Repository\SeasonRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SeasonRepository::class)
 */
class Season
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=TVShow::class, inversedBy="seasons")
     * @ORM\JoinColumn(nullable=false)
     */
    private $tvShow;

    /**
     * @ORM\Column(type="integer")
     */
    private $seasonNumber;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $summary;

    /**
     * @ORM\OneToMany(targetEntity=Episode::class, mappedBy="season", orphanRemoval=true)
     */
    private $episodes;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $title;

    public function __construct()
    {
        $this->episodes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTvShow(): ?TVShow
    {
        return $this->tvShow;
    }

    public function setTvShow(?TVShow $tvShow): self
    {
        $this->tvShow = $tvShow;

        return $this;
    }

    public function getSeasonNumber(): ?int
    {
        return $this->seasonNumber;
    }

    public function setSeasonNumber(int $seasonNumber): self
    {
        $this->seasonNumber = $seasonNumber;

        return $this;
    }

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function setSummary(?string $summary): self
    {
        $this->summary = $summary;

        return $this;
    }

    /**
     * @return Collection<int, Episode>
     */
    public function getEpisodes(): Collection
    {
        return $this->episodes;
    }

    public function addEpisode(Episode $episode): self
    {
        if (!$this->episodes->contains($episode)) {
            $this->episodes[] = $episode;
            $episode->setSeason($this);
        }

        return $this;
    }

    public function removeEpisode(Episode $episode): self
    {
        if ($this->episodes->removeElement($episode)) {
            // set the owning side to null (unless already changed)
            if ($episode->getSeason() === $this) {
                $episode->setSeason(null);
            }
        }

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array 
    {
        $toArray = [
            'id' => $this->getId(),
            'name' => $this->getTitle(),
            'seasonNumber' => $this->getSeasonNumber(),
            'summary' => $this->getSummary(),
            'episodes' => [],
        ];

        foreach($this->getEpisodes() as $episode) {
            $toArray['episodes'][] = $episode->toArray();
        }

        return $toArray;
    }
}
