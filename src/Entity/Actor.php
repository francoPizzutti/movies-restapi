<?php

namespace App\Entity;

use App\Entity\Movie;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\ActorRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity(repositoryClass=ActorRepository::class)
 */
class Actor
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $fullName;

    /**
     * @ORM\Column(type="date")
     */
    private $birthDate;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $instagramProfile;

    /**
     * @ORM\ManyToMany(targetEntity=Movie::class, mappedBy="actors")
     */
    private $movies;

    /**
     * @ORM\ManyToMany(targetEntity=TVShow::class, mappedBy="actors")
     */
    private $tvShows;

    public function __construct()
    {
        $this->movies = new ArrayCollection();
        $this->tvShows = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): self
    {
        $this->fullName = $fullName;

        return $this;
    }

    public function getBirthDate(): ?\DateTimeInterface
    {
        return $this->birthDate;
    }

    public function setBirthDate(\DateTimeInterface $birthDate): self
    {
        $this->birthDate = $birthDate;

        return $this;
    }

    public function getInstagramProfile(): ?string
    {
        return $this->instagramProfile;
    }

    public function setInstagramProfile(?string $instagramProfile): self
    {
        $this->instagramProfile = $instagramProfile;

        return $this;
    }

    /**
     * @return Collection<int, Movie>
     */
    public function getMovies(): Collection
    {
        return $this->movies;
    }

    public function addMovie(Movie $movie): self
    {
        if (!$this->movies->contains($movie)) {
            $this->movies[] = $movie;
            $movie->addActor($this);
        }

        return $this;
    }

    public function removeMovie(Movie $movie): self
    {
        if ($this->movies->removeElement($movie)) {
            $movie->removeActor($this);
        }

        return $this;
    }

    /**
     * @return string[]
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'fullName' => $this->getFullName(),
            'birthDate' => $this->getBirthDate()->format('Y-m-d'),
            'instagramProfile' => $this->getInstagramProfile(),
        ];
    }

    /**
     * @return Collection<int, TVShow>
     */
    public function getTvShows(): Collection
    {
        return $this->tvShows;
    }

    public function addTvShow(TVShow $tvShow): self
    {
        if (!$this->tvShows->contains($tvShow)) {
            $this->tvShows[] = $tvShow;
            $tvShow->addActor($this);
        }

        return $this;
    }

    public function removeTvShow(TVShow $tvShow): self
    {
        if ($this->tvShows->removeElement($tvShow)) {
            $tvShow->removeActor($this);
        }

        return $this;
    }
}
