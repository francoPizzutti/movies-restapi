<?php 

namespace App\Controller;

use Exception;
use App\Entity\Actor;
use App\Entity\Movie;
use DateTimeImmutable;
use App\Entity\Director;
use App\Form\Model\ActorDto;
use App\Form\Model\MovieDto;
use Psr\Log\LoggerInterface;
use App\Event\ListMoviesEvent;
use App\Event\CreateMovieEvent;
use App\Form\Type\ActorFormType;
use App\Form\Type\MovieFormType;
use Symfony\Component\Form\Forms;
use App\Repository\ActorRepository;
use App\Repository\MovieRepository;
use App\Model\Movie\MovieListCriteria;

use App\Repository\DirectorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;

class ActorController extends AbstractController {
    private EntityManagerInterface $entityManager;
    private MovieRepository $movieRepository;
    private ValidatorInterface $validator;
    private DirectorRepository $directorRepository;
    private ActorRepository $actorRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        ActorRepository $actorRepository,
        ValidatorInterface $validator
    ) {
        $this->actorRepository = $actorRepository;
        $this->entityManager = $entityManager;
        $this->validator = $validator;
    }


    public function addActor(Request $request): JsonResponse
    {
        $actorDto = new ActorDto();
        // Little tweak to support CSRF
        $form = Forms::createFormFactoryBuilder()
            ->addExtension(new HttpFoundationExtension())
            ->getFormFactory()->create(ActorFormType::class, $actorDto);
        
        $jsonDataArray = json_decode($request->getContent(), true);
        $form->submit($jsonDataArray);
        $errors = $this->validator->validate($actorDto);

        if(!($form->isValid() && empty(count($errors->getIterator())))) {
            $errorArray = [];
            foreach ($errors->getIterator() as $error) {
                $errorArray[] = $error->getMessage();
            }
        
            return new JsonResponse(['errors' => $errorArray], JsonResponse::HTTP_BAD_REQUEST);
        }
            
        $actor = $this->actorRepository->findOneBy([
            'fullName' => $actorDto->fullName,
            'birthDate' => DateTimeImmutable::createFromFormat('Y-m-d', $actorDto->birthDate),
            'instagramProfile' => $actorDto->instagramProfile
        ]);

        if(!empty($actor)) {
            return new JsonResponse([
                'errors' => 'The actor already exists',
                'actorId' => $actor->getId()
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $actor = new Actor();
        $actor->setFullName($actorDto->fullName);
        $actor->setBirthDate(DateTimeImmutable::createFromFormat('Y-m-d', $actorDto->birthDate));
        $actor->setInstagramProfile($actorDto->instagramProfile);

        $this->entityManager->persist($actor);
        $this->entityManager->flush();
        
        return new JsonResponse($actor->toArray(), JsonResponse::HTTP_OK);
    }

    public function updateActor(Request $request, string $actorId): JsonResponse
    {  
        if(empty($actorId)) {
            return new JsonResponse(['error' => 'Parameter actorId must be provided'], JsonResponse::HTTP_BAD_REQUEST);
        }
    
        $actorDto = new ActorDto();
        $form = Forms::createFormFactoryBuilder()
            ->addExtension(new HttpFoundationExtension())
            ->getFormFactory()->create(ActorFormType::class, $actorDto);
    
        $jsonDataArray = json_decode($request->getContent(), true);
        $form->submit($jsonDataArray);
        $errors = $this->validator->validate($actorDto);

        if(!($form->isValid() && empty(count($errors->getIterator())))) {
            $errorArray = [];
            foreach ($errors->getIterator() as $error) {
                $errorArray[] = $error->getMessage();
            }
        
            return new JsonResponse(['errors' => $errorArray], JsonResponse::HTTP_BAD_REQUEST);
        }

        $actor = $this->actorRepository->find($actorId);
        if(empty($actor)) {
            return new JsonResponse(['error' => 'Actor not found for provided actorId'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $actor->setFullName($actorDto->fullName);
        $actor->setBirthDate(DateTimeImmutable::createFromFormat('Y-m-d', $actorDto->birthDate));
        $actor->setInstagramProfile($actorDto->instagramProfile);

        $this->entityManager->persist($actor);
        $this->entityManager->flush();

        return new JsonResponse($actor->toArray(), JsonResponse::HTTP_OK);
    }

    public function listActors(string $actorId = ''): JsonResponse
    {
        if(!empty($actorId)) {
            $actor = $this->actorRepository->find($actorId);
            
            if(!empty($actor)) {
                return new JsonResponse($actor->toArray(), JsonResponse::HTTP_OK);
            }

            return new JsonResponse(['error' => 'No director found with provided actorId'], JsonResponse::HTTP_OK);
            
        }

        $actors = $this->actorRepository->findAll();

        $results = [];
        foreach($actors as $actor) {
            $results[] = $actor->toArray(); //rustic serialize method
        }

        $data = [
            'results' => $results,
            'total' => count($results) //useful when implementing frontend pagination
        ];

        return new JsonResponse($data, 200);
    }

    public function deleteActor(string $actorId): JsonResponse
    {  
        if(empty($actorId)) {
            return new JsonResponse(['error' => 'Parameter actorId must be provided'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $actor = $this->actorRepository->find($actorId);

        if(empty($actor)) {
            return new JsonResponse([
                'error' => 'Actor not found for specified actorId'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
        
        $this->actorRepository->remove($actor, true);
        return new JsonResponse([], JsonResponse::HTTP_OK);
    }
}