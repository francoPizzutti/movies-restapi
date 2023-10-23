<?php 

namespace App\Service;

use App\Entity\Movie;
use App\Form\Model\MovieDto;
use App\Repository\ActorRepository;
use App\Repository\MovieRepository;
use App\Exception\NotFoundException;
use App\Model\Movie\MovieListCriteria;
use App\Repository\DirectorRepository;

class MovieService {
    private MovieRepository $movieRepository;
    private ActorRepository $actorRepository;
    private ActorService $actorService;
    private DirectorService $directorService;

    public function __construct(
        MovieRepository $movieRepository,
        ActorRepository $actorRepository,
        DirectorRepository $directorRepository,
        ActorService $actorService,
        DirectorService $directorService
    )
    {
        $this->movieRepository = $movieRepository;
        $this->actorRepository = $actorRepository;
        $this->actorService = $actorService;
        $this->directorService = $directorService;
    }

    public function listSingleMovie(string $movieId): Movie
    {
        return $this->getMovie($movieId);
    }

    /**
     * @throws NotFoundException
     */
    public function getMovie($movieId): Movie
    {
        $movie = $this->movieRepository->find($movieId);
        if(empty($movie)) {
            throw new NotFoundException('Movie');
        }

        return $movie;
    }

    /**
     * @return mixed[] $result
     */
    public function listMovies(MovieListCriteria $criteria): array
    {
        [$movies, $totals] = $this->movieRepository->findByCriteria($criteria);

        $results = [];
        foreach($movies as $movie) {
            $movieArray = $movie->toArray();
            $results[] = $movieArray;
        }

        $data = [
            'results' => $results,
            'total' => $totals //useful when implementing frontend pagination
        ];

        return $data;
    }

    /**
     * @throws NotFoundException
     */
    public function addNewMovie(MovieDto $movieDto): Movie
    {
        $director = $this->directorService->getDirector($movieDto->directorId);

        $actors = $this->actorRepository->findByIdCollection($movieDto->actorIds);
        $this->actorService->validateActors($movieDto->actorIds, $actors);

        $movie = new Movie();
        $movie->setDirector($director);
        $movie->setName($movieDto->name);
        $movie->setGenre($movieDto->genre);
        $movie->setDuration((int) $movieDto->duration);

        foreach($actors as $actor) {
            $movie->addActor($actor);
        }

        $this->movieRepository->add($movie, true);

        return $movie;
    }

    public function updateExistingMovie(string $movieId, MovieDto $movieDto): Movie
    {
        $movie = $this->getMovie($movieId);

        //gets movie director
        $director = $this->directorService->getDirector($movieDto->directorId);

        //setup new actors for movie
        $newActors = $this->actorRepository->findByIdCollection($movieDto->actorIds);
        $this->actorService->validateActors($movieDto->actorIds, $newActors);
        $this->updateMovieActors($movie, $movieDto, $newActors);

        $movie->setName($movieDto->name);
        $movie->setGenre($movieDto->genre);
        $movie->setDuration($movieDto->duration);
        $movie->setDirector($director);


        $this->movieRepository->add($movie, true);

        return $movie;
    }

    public function removeMovie(string $movieId): void
    {
        $movie = $this->getMovie($movieId);
        $this->movieRepository->remove($movie, true);
    }

    public function updateMovieActors(Movie &$movie, MovieDto $movieDto, array $newActors): void
    {
        foreach ($movie->getActors() as $previousActor) {
            if(!in_array($previousActor->getId(), $movieDto->actorIds)) {
                $movie->removeActor($previousActor);
            }
        }

        foreach($newActors as $actor) {
            $movie->addActor($actor);
        }
    }
}
