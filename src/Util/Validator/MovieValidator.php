<?php 

namespace App\Util\Validator;

use Particle\Validator\Validator;

class MovieValidator extends Validator
{
    /**
     * @var string[]
     */
    public const MANDATORY_MOVIE_FIELDS = [
        'Name',
        'Genre',
        'Duration'
    ];
}
