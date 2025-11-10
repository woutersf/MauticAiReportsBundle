<?php

namespace MauticPlugin\MauticAiReportsBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

class AiReportsLogRepository extends CommonRepository
{
    public function getLogsByUserId($userId, $limit = 100)
    {
        return $this->createQueryBuilder('a')
            ->where('a.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('a.timestamp', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getRecentLogs($limit = 50)
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.timestamp', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
