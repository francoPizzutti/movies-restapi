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
use Particle\Validator\Rule\Json;
use Symfony\Component\Form\Forms;
use App\Repository\ActorRepository;
use App\Exception\NotFoundException;
use App\Repository\TVShowRepository;
use App\Repository\DirectorRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Exception\FailedCreationException;
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
        try {
            if(!empty($tvShowId)) {
                $shows = $this->tvShowService->listShows($tvShowId);
                return new JsonResponse($shows, JsonResponse::HTTP_OK);
            }

            $shows = $this->tvShowService->listShows();
            return new JsonResponse($shows, JsonResponse::HTTP_OK);

        } catch(NotFoundException $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
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

        try {
            $tvShow = $this->tvShowService->createTvShow($tvShowDto);
        } catch(FailedCreationException $e) {
            return new JsonResponse(['errors' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($tvShow->toArray(), 200);
    }

    public function editTvShow(Request $request, string $tvShowId): JsonResponse
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

        $tvShow = $this->tvShowService->updateTvShow($tvShowId, $tvShowDto);

        return new JsonResponse($tvShow->toArray(), 200);
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