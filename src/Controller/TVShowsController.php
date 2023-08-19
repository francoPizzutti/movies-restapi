<?php 

namespace App\Controller;

use App\Entity\Actor;
use App\Entity\TVShow;
use DateTimeImmutable;
use App\Entity\Director;
use Psr\Log\LoggerInterface;
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
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        DirectorRepository $directorRepository,
        ActorRepository $actorRepository,
        TVShowRepository $tvShowRepository,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->validator = $validator;
        $this->directorRepository = $directorRepository;
        $this->actorRepository = $actorRepository;
        $this->tvShowRepository = $tvShowRepository;
        $this->logger = $logger;
    }

    public function list(Request $request): JsonResponse
    {
        $sortBy = $request->get('sortBy');
        $response = new JsonResponse();
        $response->setData([
            'results' => 'placeholder2',
            //'total' => count($results) TODO IMPLEMENT $results logic
        ]);
        
        return $response;
    }

    public function addTvShow(Request $request): JsonResponse
    {
        $tvShowDto = new TVShowDto();
        // Little tweak to support CSRF
        $form = Forms::createFormFactoryBuilder()
            ->addExtension(new HttpFoundationExtension())
            ->getFormFactory()->create(TVShowFormType::class, $tvShowDto);
        
        $jsonDataArray = json_decode($request->getContent(), true);
        $form->submit($jsonDataArray);

        $errors = $this->validator->validate($tvShowDto);

        if($form->isValid() && empty(count($errors->getIterator()))) {
            //director setup
            
            $directorDto = $tvShowDto->director;
            $director = $this->directorRepository->findOneBy([
                'fullName' => $directorDto->fullName,
                'birthDate' => DateTimeImmutable::createFromFormat('Y-m-d', $directorDto->birthDate),
                'instagramProfile' => $directorDto->instagramProfile
            ]);

            if(empty($director)) {
                $director = new Director();
                $director->setFullName($directorDto->fullName);
                $director->setBirthDate(DateTimeImmutable::createFromFormat('Y-m-d', $directorDto->birthDate));
                $director->setBiography($directorDto->biography);
                $director->setInstagramProfile($directorDto->instagramProfile);
                $this->entityManager->persist($director);
            }

            //tvShow setup
            $tvShow = new TVShow();
            $tvShow->setTitle($tvShowDto->name);
            $tvShow->setGenre($tvShowDto->genre);
            $tvShow->setReleaseDate(DateTimeImmutable::createFromFormat('Y-m-d', $tvShowDto->releaseDate));

            //actors setup
            foreach($tvShowDto->actors as $actorDto) {
                $actor = $this->actorRepository->findOneBy([
                    'fullName' => $actorDto->fullName,
                    'birthDate' => DateTimeImmutable::createFromFormat('Y-m-d', $actorDto->birthDate),
                    'instagramProfile' => $actorDto->instagramProfile
                ]);

                if(empty($actor)) {
                    $actor = new Actor();
                    $actor->setFullName($actorDto->fullName);
                    $actor->setBirthDate(DateTimeImmutable::createFromFormat('Y-m-d', $actorDto->birthDate));
                    $actor->setInstagramProfile($actorDto->instagramProfile);
                    $this->entityManager->persist($actor);
                }

                $tvShow->addActor($actor);
            }
            
            $this->entityManager->persist($tvShow);
            $this->entityManager->flush();

            return new JsonResponse($tvShow->toArray(), JsonResponse::HTTP_OK);
        } 

        $errorArray = [];
        foreach ($errors->getIterator() as $error) {
            $errorArray[] = $error->getMessage();
        }
    
        return new JsonResponse(['errors' => $errorArray], JsonResponse::HTTP_BAD_REQUEST);
    }
}