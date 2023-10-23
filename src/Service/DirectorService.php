<?php 

namespace App\Service;

use Exception;
use DateTimeImmutable;
use App\Entity\Director;
use App\Form\Model\DirectorDto;
use App\Exception\NotFoundException;
use App\Repository\DirectorRepository;

class DirectorService {
    private DirectorRepository $directorRepository;
    
    public function __construct(
        DirectorRepository $directorRepository
    ) {
        $this->directorRepository = $directorRepository;
    }

    public function addDirector(DirectorDto $directorDto): Director
    {
        $this->validateTupleDoesNotExists($directorDto);
        $director = new Director();
        $director->setFullName($directorDto->fullName);
        $director->setBirthDate(DateTimeImmutable::createFromFormat('Y-m-d', $directorDto->birthDate));
        $director->setBiography($directorDto->biography);
        $director->setInstagramProfile($directorDto->instagramProfile);

        $this->directorRepository->add($director, true);

        return $director;
    }

    public function updateExistingDirector(string $directorId, DirectorDto $directorDto): Director
    {
        $director = $this->getDirector($directorId);
        $director->setFullName($directorDto->fullName);
        $director->setBirthDate(DateTimeImmutable::createFromFormat('Y-m-d', $directorDto->birthDate));
        $director->setBiography($directorDto->biography);
        $director->setInstagramProfile($directorDto->instagramProfile);
        $this->directorRepository->add($director, true);
    }

    /**
     * @throws NotFoundException
     */
    public function getDirector($directorId): Director
    {
        $movie = $this->directorRepository->find($directorId);
        if(empty($movie)) {
            throw new NotFoundException('Director');
        }

        return $movie;
    }
    
    public function listSingleDirector(string $directorId): Director
    {
        return $this->getDirector($directorId);
    }
    
    /**
     * @return mixed[]
     */
    public function listAll(): array
    {
        $directors = $this->directorRepository->findAll();

        $results = [];
        foreach($directors as $director) {
            $results[] = $director->toArray();
        }

        $data = [
            'results' => $results,
            'total' => count($results) //not adding value bc no pagination implemented for the entity
        ];

        return $data;
    }

    public function removeDirector(string $directorId): void
    {
        $director = $this->getDirector($directorId);
        $this->directorRepository->remove($director, true);
    }

    public function validateTupleDoesNotExists(DirectorDto $directorDto): void
    {
        //this basically applies unique index by whole tuple data via code.
        $director = $this->directorRepository->findOneBy([
            'fullName' => $directorDto->fullName,
            'birthDate' => DateTimeImmutable::createFromFormat('Y-m-d', $directorDto->birthDate),
            'biography' => $directorDto->biography,
            'instagramProfile' => $directorDto->instagramProfile
        ]);

        if(!empty($director)) {
            throw new Exception(sprintf('The director already exists (ID: %d', $director->getId()));
        }
    }
}
