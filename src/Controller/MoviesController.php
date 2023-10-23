<?php 

namespace App\Controller;

use Throwable;
use App\Form\Model\MovieDto;
use App\Service\MovieService;
use App\Form\Type\MovieFormType;
use Symfony\Component\Form\Forms;
use App\Exception\NotFoundException;
use App\Model\Movie\MovieListCriteria;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;

class MoviesController extends AbstractController {
    private ValidatorInterface $validator;
    private MovieService $movieService;

    public function __construct(
        MovieService $movieService,
        ValidatorInterface $validator
    ) {
        $this->movieService = $movieService;
        $this->validator = $validator;
    }

    public function listMovies(Request $request, string $movieId): JsonResponse
    {
        if(!empty($movieId)) {
            try {
                $movie = $this->movieService->listSingleMovie($movieId);
                return new JsonResponse($movie->toArray(), JsonResponse::HTTP_OK);
            } catch (NotFoundException $e) {
                return new JsonResponse([
                    'error' => $e->getMessage(),
                ], JsonResponse::HTTP_BAD_REQUEST);
            }
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
        
        return new JsonResponse($this->movieService->listMovies($criteria), 200);
    }

    public function addMovie(Request $request): JsonResponse
    {
        $movieDto = new MovieDto();
        // tweak to support CSRF
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

        try {
            $movie = $this->movieService->addNewMovie($movieDto);
        } catch(Throwable $th) {
            return new JsonResponse([
                'error' => $th->getMessage(),
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
        
        return new JsonResponse($movie->toArray(), JsonResponse::HTTP_OK);
    }

    public function updateMovie(Request $request, string $movieId): JsonResponse
    {
        if(empty($movieId)) {
            return new JsonResponse(['error' => 'Parameter movieId must be provided'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $movieDto = new MovieDto();
        // tweak to support CSRF
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

        try {
            $movie = $this->movieService->updateExistingMovie($movieId, $movieDto);
        } catch(NotFoundException $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($movie->toArray(), JsonResponse::HTTP_OK);
    }

    public function deleteMovie(string $movieId): JsonResponse
    {
        if(empty($movieId)) {
            return new JsonResponse(['error' => 'Parameter movieId must be provided'], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $this->movieService->removeMovie($movieId);
        } catch(NotFoundException $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([], JsonResponse::HTTP_OK);
    }
}