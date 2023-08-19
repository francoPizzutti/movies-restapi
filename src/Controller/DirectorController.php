<?php 

namespace App\Controller;

use Exception;
use App\Entity\Movie;
use DateTimeImmutable;
use App\Entity\Director;
use App\Form\Model\DirectorDto;
use App\Form\Model\MovieDto;
use Psr\Log\LoggerInterface;
use App\Event\ListMoviesEvent;
use App\Event\CreateMovieEvent;
use App\Form\Type\DirectorFormType;
use App\Form\Type\MovieFormType;
use Symfony\Component\Form\Forms;
use App\Repository\MovieRepository;
use App\Model\Movie\MovieListCriteria;
use App\Repository\DirectorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormFdirectoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;

class DirectorController extends AbstractController {
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;
    private DirectorRepository $directorRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        DirectorRepository $directorRepository,
        ValidatorInterface $validator
    ) {
        $this->directorRepository = $directorRepository;
        $this->entityManager = $entityManager;
        $this->validator = $validator;
    }

    public function addDirector(Request $request): JsonResponse
    {
        $directorDto = new directorDto();
        // Little tweak to support CSRF
        $form = Forms::createFormFactoryBuilder()
            ->addExtension(new HttpFoundationExtension())
            ->getFormFactory()->create(DirectorFormType::class, $directorDto);
        
        $jsonDataArray = json_decode($request->getContent(), true);
        $form->submit($jsonDataArray);
        $errors = $this->validator->validate($directorDto);

        if(!($form->isValid() && empty(count($errors->getIterator())))) {
            $errorArray = [];
            foreach ($errors->getIterator() as $error) {
                $errorArray[] = $error->getMessage();
            }
        
            return new JsonResponse(['errors' => $errorArray], JsonResponse::HTTP_BAD_REQUEST);
        }
            
        $director = $this->directorRepository->findOneBy([
            'fullName' => $directorDto->fullName,
            'birthDate' => DateTimeImmutable::createFromFormat('Y-m-d', $directorDto->birthDate),
            'biography' => $directorDto->biography,
            'instagramProfile' => $directorDto->instagramProfile
        ]);

        if(!empty($director)) {
            return new JsonResponse([
                'errors' => 'The director already exists',
                'directorId' => $director->getId()
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $director = new director();
        $director->setFullName($directorDto->fullName);
        $director->setBirthDate(DateTimeImmutable::createFromFormat('Y-m-d', $directorDto->birthDate));
        $director->setBiography($directorDto->biography);
        $director->setInstagramProfile($directorDto->instagramProfile);

        $this->entityManager->persist($director);
        $this->entityManager->flush();
        
        return new JsonResponse($director->toArray(), JsonResponse::HTTP_OK);
    }

    public function updateDirector(Request $request, ?string $directorId = null): JsonResponse
    {  
        if(empty($directorId)) {
            return new JsonResponse(['error' => 'Parameter directorId must be provided'], JsonResponse::HTTP_BAD_REQUEST);
        }
    
        $directorDto = new directorDto();
        $form = Forms::createFormFactoryBuilder()
            ->addExtension(new HttpFoundationExtension())
            ->getFormFactory()->create(DirectorFormType::class, $directorDto);
    
        $jsonDataArray = json_decode($request->getContent(), true);
        $form->submit($jsonDataArray);
        $errors = $this->validator->validate($directorDto);

        if(!($form->isValid() && empty(count($errors->getIterator())))) {
            $errorArray = [];
            foreach ($errors->getIterator() as $error) {
                $errorArray[] = $error->getMessage();
            }
        
            return new JsonResponse(['errors' => $errorArray], JsonResponse::HTTP_BAD_REQUEST);
        }

        $jsonDataArray = json_decode($request->getContent(), true);
        $director = $this->directorRepository->find($directorId);
        $director->setFullName($directorDto->fullName);
        $director->setBirthDate(DateTimeImmutable::createFromFormat('Y-m-d', $directorDto->birthDate));
        $director->setBiography($directorDto->biography);
        $director->setInstagramProfile($directorDto->instagramProfile);

        $this->entityManager->persist($director);
        $this->entityManager->flush();

        return new JsonResponse($director->toArray(), JsonResponse::HTTP_OK);
    }

    public function listDirectors(Request $request, string $directorId = ''): JsonResponse
    {
        if(!empty($directorId)) {
            $director = $this->directorRepository->find($directorId);

            if(!empty($director)) {
                return new JsonResponse($director->toArray(), JsonResponse::HTTP_OK);
            }

            return new JsonResponse(['error' => 'No director found with provided directorId'], JsonResponse::HTTP_OK);
            
        }

        $directors = $this->directorRepository->findAll();

        $results = [];
        foreach($directors as $director) {
            $results[] = $director->toArray(); //rustic serialize method
        }

        $data = [
            'results' => $results,
            'total' => count($results) //useful when implementing frontend pagination
        ];

        return new JsonResponse($data, JsonResponse::HTTP_OK);
    }

    public function deleteDirector(Request $request, string $directorId): JsonResponse
    {  
        if(empty($directorId)) {
            return new JsonResponse(['error' => 'Parameter directorId must be provided'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $director = $this->directorRepository->find($directorId);

        if(empty($director)) {
            return new JsonResponse([
                'error' => 'Director not found for specified directorId'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $this->directorRepository->remove($director, true);
        return new JsonResponse([], JsonResponse::HTTP_OK);
    }
}