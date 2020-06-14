<?php

namespace Sourceml\Repository\Sources;

use Doctrine\ORM\EntityRepository;

use Sourceml\Entity\App\User;
use Sourceml\Entity\JQFileUpload\Media;
use Sourceml\Entity\Sources\Source;

class SourceAuthorRepository extends EntityRepository {

    public function getNotValidatedSourceAuthors(User $user) {
        $em = $this->getEntityManager();
        $query = $em->createQuery("
            SELECT sa FROM Sourceml\Entity\Sources\SourceAuthor sa
            JOIN sa.author a
            JOIN a.user u
            Where u.id = :user_id
            and sa.isValid = false
        ")->setParameters(
            array(
                "user_id" => $user->getId()
            )
        );
        return $query->getResult();
    }

}
