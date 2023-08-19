<?php

namespace App\Entity;

use App\Repository\EpisodeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=EpisodeRepository::class)
 */
class Episode
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Director::class, inversedBy="episodes")
     * @ORM\JoinColumn(nullable=false)
     */
    private $director;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $title;

    /**
     * @ORM\Column(type="integer")
     */
    private $episodeNumber;

    /**
     * @ORM\Column(type="date")
     */
    private $releaseDate;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $episodeSummary;

    /**
     * @ORM\ManyToOne(targetEntity=Season::class, inversedBy="episodes")
     * @ORM\JoinColumn(nullable=false)
     */
    private $season;

    /**
     * @ORM\ManyToMany(targetEntity=Actor::class)
     */
    private $invitedActors;

    public function __construct()
    {
        $this->invitedActors = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDirector(): ?Director
    {
        return $this->director;
    }

    public function setDirector(?Director $director): self
    {
        $this->director = $director;

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

    public function getEpisodeNumber(): ?int
    {
        return $this->episodeNumber;
    }

    public function setEpisodeNumber(int $episodeNumber): self
    {
        $this->episodeNumber = $episodeNumber;

        return $this;
    }

    public function getReleaseDate(): ?\DateTimeInterface
    {
        return $this->releaseDate;
    }

    public function setReleaseDate(\DateTimeInterface $releaseDate): self
    {
        $this->releaseDate = $releaseDate;

        return $this;
    }

    public function getEpisodeSummary(): ?string
    {
        return $this->episodeSummary;
    }

    public function setEpisodeSummary(?string $episodeSummary): self
    {
        $this->episodeSummary = $episodeSummary;

        return $this;
    }

    public function getSeason(): ?Season
    {
        return $this->season;
    }

    public function setSeason(?Season $season): self
    {
        $this->season = $season;

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
            'invitedActors' => [],
        ];

        foreach($this->getInvitedActors() as $actor) {
            $toArray['invitedActors'][] = $actor->toArray();
        }

        return $toArray;
    }

    /**
     * @return Collection<int, Actor>
     */
    public function getInvitedActors(): Collection
    {
        return $this->invitedActors;
    }

    public function addInvitedActor(Actor $invitedActor): self
    {
        if (!$this->invitedActors->contains($invitedActor)) {
            $this->invitedActors[] = $invitedActor;
        }

        return $this;
    }

    public function removeInvitedActor(Actor $invitedActor): self
    {
        $this->invitedActors->removeElement($invitedActor);

        return $this;
    }
}
