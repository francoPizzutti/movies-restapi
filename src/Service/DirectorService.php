<?php 

namespace App\Service;

use App\Exception\NotFoundException;
use App\Repository\DirectorRepository;

class DirectorService {
    private DirectorRepository $directorRepository;
    
    public function __construct(
        DirectorRepository $directorRepository
    ) {
        $this->directorRepository = $directorRepository;
    }

    /**
     * @throws NotFoundException
     */
    public function validateDirector(string $directorId): void
    {
        $director = $this->directorRepository->find($directorId);
        if(empty($director)) {
            throw new NotFoundException('Director');
        }
    }

}
