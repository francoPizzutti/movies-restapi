<?php 

namespace App\Controller;

use Exception;
use Throwable;
use App\Form\Model\DirectorDto;
use App\Service\DirectorService;
use Symfony\Component\Form\Forms;
use App\Form\Type\DirectorFormType;
use App\Repository\DirectorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;

class DirectorController extends AbstractController {
    private ValidatorInterface $validator;
    private DirectorRepository $directorRepository;
    private DirectorService $directorService;
    
    public function __construct(
        DirectorService $directorService,
        DirectorRepository $directorRepository,
        ValidatorInterface $validator
    ) {
        $this->directorRepository = $directorRepository;
        $this->directorService = $directorService;
        $this->validator = $validator;
    }

    public function addDirector(Request $request): JsonResponse
    {
        $directorDto = new DirectorDto();
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
        
        try {
            $director = $this->directorService->addDirector($directorDto);
            return new JsonResponse($director->toArray(), JsonResponse::HTTP_OK);
        } catch(Throwable $th) {
            return new JsonResponse(['errors' => $th->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
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

        try {
            $director = $this->directorService->updateExistingDirector($directorId, $directorDto);
            return new JsonResponse($director->toArray(), JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            return new JsonResponse(['errors' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    public function listDirectors(Request $request, string $directorId = ''): JsonResponse
    {
        if(!empty($directorId)) {
            $director = $this->directorService->listSingleDirector($directorId);
            return new JsonResponse($director->toArray(), JsonResponse::HTTP_OK);
        }

        $data = $this->directorService->listAll();
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

        $this->directorService->removeDirector($directorId);
        return new JsonResponse([], JsonResponse::HTTP_OK);
    }
}