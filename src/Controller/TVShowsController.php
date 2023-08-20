<?php 

namespace App\Controller;

use App\Entity\Actor;
use App\Entity\TVShow;
use DateTimeImmutable;
use App\Entity\Director;
use Psr\Log\LoggerInterface;
use App\Form\Model\TvShowDto;
use App\Service\TvShowService;
use App\Form\Type\TvShowFormType;
use Symfony\Component\Form\Forms;
use App\Repository\ActorRepository;
use App\Repository\TVShowRepository;
use App\Repository\DirectorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;


class TVShowsController extends AbstractController {
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;
    private DirectorRepository $directorRepository;
    private ActorRepository $actorRepository;
    private TVShowRepository $tvShowRepository;
    private TvShowService $tvShowService;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        DirectorRepository $directorRepository,
        ActorRepository $actorRepository,
        TVShowRepository $tvShowRepository,
        TvShowService $tvShowService,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->validator = $validator;
        $this->directorRepository = $directorRepository;
        $this->actorRepository = $actorRepository;
        $this->tvShowRepository = $tvShowRepository;
        $this->tvShowService = $tvShowService;
        $this->logger = $logger;
    }

    public function listTvShows(string $tvShowId): JsonResponse
    {
        if(!empty($tvShowId)) {
            $tvShow = $this->tvShowRepository->find($tvShowId);
            if(empty($tvShow)) {
                return new JsonResponse([
                    'error' => 'TV Show not found for specified movieId'
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            return new JsonResponse($tvShow->toArray(), JsonResponse::HTTP_OK);
        }

        $results = $this->tvShowService->getShowsList();

        $data = [
            'results' => $results,
            'total' => count($results) //useful when implementing frontend pagination
        ];

        return new JsonResponse($data, JsonResponse::HTTP_OK);
    }

    public function addTvShow(Request $request): JsonResponse
    {
        $tvShowDto = new TvShowDto();
        // Little tweak to support CSRF
        $form = Forms::createFormFactoryBuilder()
            ->addExtension(new HttpFoundationExtension())
            ->getFormFactory()->create(TvShowFormType::class, $tvShowDto);
        
        $jsonDataArray = json_decode($request->getContent(), true);
        $form->submit($jsonDataArray);

        $errors = $this->validator->validate($tvShowDto);

        if(!($form->isValid() && empty(count($errors->getIterator())))) {
            $errorArray = [];
            foreach ($errors->getIterator() as $error) {
                $errorArray[] = $error->getMessage();
            }
            
            return new JsonResponse(['errors' => $errorArray], JsonResponse::HTTP_BAD_REQUEST);
        }

       $tvShow = $this->tvShowService->createTvShow($tvShowDto);

        return new JsonResponse([], 200);
    }

    public function deleteTvShow(string $tvShowId): JsonResponse
    {
        if(empty($tvShowId)) {
            return new JsonResponse(['error' => 'Parameter tvShowId must be provided'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $this->tvShowService->removeTvShow($tvShowId);
        return new JsonResponse([], JsonResponse::HTTP_OK);
    }
}