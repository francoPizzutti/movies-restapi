<?php 

namespace App\Service;

use App\Repository\ActorRepository;
use App\Exception\FailedCreationException;

class ActorService {
    private ActorRepository $actorRepository;

    public function __construct(
        ActorRepository $actorRepository
    )
    {
        $this->actorRepository = $actorRepository;
    }

    /**
     * @throws FailedCreationException
     */
    public function validateActors(array $dtoActorIds): void
    {
        if(empty($dtoActorIds)) {
            return;
        }

        $actors = $this->actorRepository->findByIdCollection($dtoActorIds);
        
        $existingActorIds = array_map(function ($actor) {
            return $actor->getId();
        }, $actors);
        
        $missingActors = array_diff($dtoActorIds, array_values($existingActorIds));
        if(!empty($missingActors)) {
            $errorMsg = 'Some provided actor ids weren\'t found. (IDs: %s)';
            $errorString =  sprintf($errorMsg, implode(',', array_values($missingActors)));

            //To-Do create new exception, used this one just to pass
            throw new FailedCreationException('TvShow', $errorString);
        }
    }

}
