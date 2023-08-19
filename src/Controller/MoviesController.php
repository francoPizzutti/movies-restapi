<?php 

namespace App\Controller;

use Exception;
use App\Entity\Actor;
use App\Entity\Movie;
use DateTimeImmutable;
use App\Entity\Director;
use App\Form\Model\MovieDto;
use Psr\Log\LoggerInterface;
use App\Event\ListMoviesEvent;
use App\Event\CreateMovieEvent;
use App\Form\Type\MovieFormType;
use Symfony\Component\Form\Forms;
use App\Repository\MovieRepository;
use App\Model\Movie\MovieListCriteria;
use App\Repository\DirectorRepository;
use App\Repository\ActorRepository;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;

class MoviesController extends AbstractController {
    private EntityManagerInterface $entityManager;
    private MovieRepository $movieRepository;
    private ValidatorInterface $validator;
    private DirectorRepository $directorRepository;
    private ActorRepository $actorRepository;

    public function __construct(
        MovieRepository $movieRepository,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        DirectorRepository $directorRepository,
        ActorRepository $actorRepository
    ) {
        $this->movieRepository = $movieRepository;
        $this->directorRepository= $directorRepository;
        $this->actorRepository = $actorRepository;
        $this->entityManager = $entityManager;
        $this->validator = $validator;
    }

    public function validateCriteria(string $criteria): bool
    {
        return empty($criteria) || in_array($criteria, Movie::MANDATORY_MOVIE_FIELDS);
    }

    public function listMovies(Request $request, string $movieId): JsonResponse
    {
        if(!empty($movieId)) {
            $movie = $this->movieRepository->find($movieId);
            if(empty($movie)) {
                return new JsonResponse([
                    'error' => 'Movie not found for specified movieId'
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            return new JsonResponse($movie->toArray(), JsonResponse::HTTP_OK);
        }

        $movieName = $request->query->get('name');
        $movieGenre = $request->query->get('genre');
        $itemsPerPage = $request->query->get('itemsPerPage');
        $page = $request->query->get('page');
        $sortBy = $request->query->get('sortBy');
        $sortOrder = $request->query->get('sortOrder');

        if(!empty($sortBy) && !in_array($sortBy, ['name', 'genre'])) {
            return new JsonResponse([
                'error' => 'sortBy parameter should be either name or genre.'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        if(!empty($sortOrder) && !in_array($sortOrder, ['ASC', 'DESC'])) {
            return new JsonResponse([
                'error' => 'sortOrder parameter should be either ASC or DESC.'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $criteria = new MovieListCriteria(
            $movieName,
            $movieGenre,
            $itemsPerPage ?? MovieListCriteria::DEFAULT_ITEMS_PER_PAGE,
            $page ?? MovieListCriteria::DEFAULT_PAGE,
            $sortBy ?? MovieListCriteria::DEFAULT_SORT_BY,
            $sortOrder ?? MovieListCriteria::DEFAULT_SORT_ORDER
        );

        $movies = $this->movieRepository->findByCriteria($criteria);

        $results = [];
        foreach($movies as $movie) {
            $movieArray = $movie->toArray(); //rustic serialize method
            $results[] = $movieArray;
        }

        $data = [
            'results' => $results,
            'total' => count($results) //useful when implementing frontend pagination
        ];

        return new JsonResponse($data, 200);
    }

    public function addMovie(Request $request)
    {
        $movieDto = new MovieDto();
        // Little tweak to support CSRF
        $form = Forms::createFormFactoryBuilder()
            ->addExtension(new HttpFoundationExtension())
            ->getFormFactory()->create(MovieFormType::class, $movieDto);
            
        $jsonDataArray = json_decode($request->getContent(), true);
        $form->submit($jsonDataArray);
        $errors = $this->validator->validate($movieDto);
        
        if(!($form->isValid() && empty(count($errors->getIterator())))) {
            $errorArray = [];
            foreach ($errors->getIterator() as $error) {
                $errorArray[] = $error->getMessage();
            }
            
            return new JsonResponse(['errors' => $errorArray], JsonResponse::HTTP_BAD_REQUEST);
        }

        $director = $this->directorRepository->find($movieDto->directorId);
        if(empty($director)) {
            return new JsonResponse(['error' => 'Couldn\'t found director with provided directorId'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $actors = $this->actorRepository->findByIdCollection($movieDto->actorIds);
        
        $existingActorIds = array_map(function ($actor) {
            return $actor->getId();
        }, $actors);
        
        if(!empty(array_diff($movieDto->actorIds, array_values($existingActorIds)))) {
            return new JsonResponse([
                'error' => 'Some provided actor ids weren\'t found',
                'missingActorIds' => array_values(array_diff($movieDto->actorIds, array_values($existingActorIds))),
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $movie = new Movie();
        $movie->setDirector($director);
        $movie->setName($movieDto->name);
        $movie->setGenre($movieDto->genre);
        $movie->setDuration((int) $movieDto->duration);
        foreach($actors as $actor) {
            $movie->addActor($actor);
        }

        $this->entityManager->persist($movie);
        $this->entityManager->flush();

        return new JsonResponse($movie->toArray(), JsonResponse::HTTP_OK);
    }

    public function updateMovie(Request $request, string $movieId): JsonResponse
    {
        if(empty($movieId)) {
            return new JsonResponse(['error' => 'Parameter movieId must be provided'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $movieDto = new MovieDto();
        // Little tweak to support CSRF
        $form = Forms::createFormFactoryBuilder()
            ->addExtension(new HttpFoundationExtension())
            ->getFormFactory()->create(MovieFormType::class, $movieDto);
            
        $jsonDataArray = json_decode($request->getContent(), true);
        $form->submit($jsonDataArray);
        $errors = $this->validator->validate($movieDto);
        
        if(!($form->isValid() && empty(count($errors->getIterator())))) { //counting errors iterator cause it's always set even when empty.
            $errorArray = [];
            foreach ($errors->getIterator() as $error) {
                $errorArray[] = $error->getMessage();
            }
            
            return new JsonResponse(['errors' => $errorArray], JsonResponse::HTTP_BAD_REQUEST);
        }

        $movie = $this->movieRepository->find($movieId);
        if(empty($movie)) {
            return new JsonResponse(['error' => 'Movie not found for provided movieId'], JsonResponse::HTTP_BAD_REQUEST);
        }

        //setup new director for movie
        $director = $this->directorRepository->find($movieDto->directorId);
        if(empty($director)) {
            return new JsonResponse(['error' => 'Director not found for provided directorId'], JsonResponse::HTTP_BAD_REQUEST);
        }

        //setup new actors
        $newActors = $this->actorRepository->findByIdCollection($movieDto->actorIds);

        $existingActorIds = array_map(function ($actor) {
            return $actor->getId();
        }, $newActors);
        
        if(!empty(array_diff($movieDto->actorIds, array_values($existingActorIds)))) {
            return new JsonResponse([
                'error' => 'Some provided actor ids weren\'t found',
                'missingActorIds' => array_values(array_diff($movieDto->actorIds, array_values($existingActorIds))),
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $movie->setName($movieDto->name);
        $movie->setGenre($movieDto->genre);
        $movie->setDuration($movieDto->duration);
        $movie->setDirector($director);
        $this->updateMovieActors($movie, $movieDto, $newActors);

        $this->entityManager->persist($movie);
        $this->entityManager->flush();

        return new JsonResponse($movie->toArray(), JsonResponse::HTTP_OK);
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

    public function deleteMovie(string $movieId): JsonResponse
    {
        if(empty($movieId)) {
            return new JsonResponse(['error' => 'Parameter movieId must be provided'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $movie = $this->movieRepository->find($movieId);
        if(empty($movie)) {
            return new JsonResponse([
                'error' => 'Movie not found for specified movieId'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $this->movieRepository->remove($movie, true);
        return new JsonResponse([], JsonResponse::HTTP_OK);
    }

    // public function addMovie(Request $request): JsonResponse
    // {
    //     $movieDto = new MovieDto();
    //     // Little tweak to support CSRF
    //     $form = Forms::createFormFactoryBuilder()
    //         ->addExtension(new HttpFoundationExtension())
    //         ->getFormFactory()->create(MovieFormType::class, $movieDto);
        
    //     $jsonDataArray = json_decode($request->getContent(), true);
    //     $form->submit($jsonDataArray);

    //     $errors = $this->validator->validate($movieDto);

    //     if($form->isValid() && empty(count($errors->getIterator()))) {
    //         //director setup
            
    //         $directorDto = $movieDto->directorId;
    //         $director = $this->directorRepository->findOneBy([
    //             'fullName' => $directorDto->fullName,
    //             'birthDate' => DateTimeImmutable::createFromFormat('Y-m-d', $directorDto->birthDate),
    //             'instagramProfile' => $directorDto->instagramProfile
    //         ]);

    //         if(empty($director)) {
    //             $director = new Director();
    //             $director->setFullName($directorDto->fullName);
    //             $director->setBirthDate(DateTimeImmutable::createFromFormat('Y-m-d', $directorDto->birthDate));
    //             $director->setBiography($directorDto->biography);
    //             $director->setInstagramProfile($directorDto->instagramProfile);
    //             $this->entityManager->persist($director);
    //         }

    //         //movie setup
    //         $movie = new Movie();
    //         $movie->setDirector($director);
    //         $movie->setName($movieDto->name);
    //         $movie->setGenre($movieDto->genre);
    //         $movie->setDuration((int) $movieDto->duration);

    //         //actors setup
    //         foreach($movieDto->actors as $actorDto) {
    //             $actor = $this->actorRepository->findOneBy([
    //                 'fullName' => $actorDto->fullName,
    //                 'birthDate' => DateTimeImmutable::createFromFormat('Y-m-d', $actorDto->birthDate),
    //                 'instagramProfile' => $actorDto->instagramProfile
    //             ]);

    //             if(empty($actor)) {
    //                 $actor = new Actor();
    //                 $actor->setFullName($actorDto->fullName);
    //                 $actor->setBirthDate(DateTimeImmutable::createFromFormat('Y-m-d', $actorDto->birthDate));
    //                 $actor->setInstagramProfile($actorDto->instagramProfile);
    //                 $this->entityManager->persist($actor);
    //             }

    //             $movie->addActor($actor);
    //         }
            
    //         $this->entityManager->persist($movie);
    //         $this->entityManager->flush();

    //         return new JsonResponse($movie->toArray(), JsonResponse::HTTP_OK);
    //     } 

    //     $errorArray = [];
    //     foreach ($errors->getIterator() as $error) {
    //         $errorArray[] = $error->getMessage();
    //     }
    
    //     return new JsonResponse(['errors' => $errorArray], JsonResponse::HTTP_BAD_REQUEST);
    // }
}