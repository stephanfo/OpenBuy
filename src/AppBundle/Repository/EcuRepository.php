<?php

namespace AppBundle\Repository;

use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * EcuRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class EcuRepository extends \Doctrine\ORM\EntityRepository
{
    public function getEcusPerPage($page, $nbPerPage)
    {
        $query = $this->createQueryBuilder('ecu')
            ->orderBy('ecu.name', 'ASC')
            ->setFirstResult(($page - 1) * $nbPerPage)
            ->setMaxResults($nbPerPage);

        return new Paginator($query, true);
    }
}
