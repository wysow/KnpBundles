<?php

namespace Knp\Bundle\KnpBundlesBundle\Repository;

use Knp\Bundle\KnpBundlesBundle\Entity\Bundle;
use Knp\Bundle\KnpBundlesBundle\Entity\Activity;
use Doctrine\ORM\EntityRepository;

class ActivityRepository extends EntityRepository
{
    /**
     * @param integer $limit
     *
     * @return array
     */
    public function findAllSortedBy($sortBy, $limit = 10)
    {
        $query = $this->queryLastActivities(null, null, null, $sortBy);
        if (null !== $limit) {
            $query->setMaxResults($limit);
        }

        return $query->execute();
    }

    /**
     * @param Bundle  $bundle
     * @param integer $limit
     *
     * @return array
     */
    public function findLastCommitsForBundle(Bundle $bundle, $limit = 10)
    {
        $query = $this->queryLastActivities(Activity::ACTIVITY_TYPE_COMMIT, $bundle->getId(), null);
        if (null !== $limit) {
            $query->setMaxResults($limit);
        }

        return $query->execute();
    }

    /**
     * @param Bundle $bundle
     *
     * @return integer
     */
    public function countActivitiesByBundle(Bundle $bundle)
    {
        return $this->createQueryBuilder('a')
            ->select('count(a.id)')
            ->where('a.bundle = :bundle')
            ->setParameter('bundle', $bundle)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * @param Bundle  $bundle
     * @param integer $limit
     *
     * @return array
     */
    public function findLastActivitiesForBundle(Bundle $bundle, $limit = 10)
    {
        $query = $this->queryLastActivities(null, $bundle->getId(), null);
        if (null !== $limit) {
            $query->setMaxResults($limit);
        }

        return $query->execute();
    }

    /**
     * @param Bundle $bundle
     *
     * @return void
     */
    public function removeActivities(Bundle $bundle, array $leftActivities)
    {
        return
            $this->_em
            ->createQuery('DELETE FROM KnpBundlesBundle:Activity a WHERE a.bundle = ?1 AND a.id NOT IN (?2)')
            ->setParameter(1, $bundle)
            ->setParameter(2, $leftActivities)
            ->execute()
        ;
    }

    public function deleteIds(array $ids)
    {
        if (empty($ids)) {
            return;
        }

        $qb = $this->_em->createQueryBuilder();

        $qb->delete('KnpBundlesBundle:Activity', 'a')
            ->where($qb->expr()->in('a.id', $ids))
            ->getQuery()
            ->execute()
        ;
    }

    /**
     * @param string  $type
     * @param integer $bundleId
     * @param integer $developerId
     * @param string  $sortBy
     *
     * @return \Doctrine\ORM\Query
     */
    public function queryLastActivities($type, $bundleId, $developerId, $sortBy = 'createdAt')
    {
        $query = $this->createQueryBuilder('a')
            ->orderBy('a.'.$sortBy, 'desc')
            ->leftJoin('a.bundle', 'b')
            ->leftJoin('a.developer', 'd')
            ->select('a, b, d')
        ;

        if (null !== $type) {
            $query
                ->where('a.type = :type')
                ->setParameter('type', $type)
            ;
        }

        if (null !== $bundleId) {
            $query
                ->andWhere('b.id = :bundle_id')
                ->setParameter('bundle_id', $bundleId)
            ;
        }

        if (null !== $developerId) {
            $query
                ->andWhere('d.id = :developer_id')
                ->setParameter('developer_id', $developerId)
            ;
        }

        return $query->getQuery();
    }

    /**
     * @param  Activity $activity
     * @return array
     */
    public function findAllSimilar(Activity $activity)
    {
        return $this
            ->createQueryBuilder('a')
            ->where('a.id != ?1')
            ->andWhere('a.author = ?2')
            ->andWhere('a.bundleName = ?3')
            ->andWhere('a.bundleOwnerName = ?4')
            ->andWhere('a.message = ?5')
            ->andWhere('a.createdAt = ?6')
            ->setParameter(1, $activity->getId())
            ->setParameter(2, $activity->getAuthor())
            ->setParameter(3, $activity->getBundleName())
            ->setParameter(4, $activity->getBundleOwnerName())
            ->setParameter(5, $activity->getMessage())
            ->setParameter(6, $activity->getCreatedAt())
            ->getQuery()
            ->getResult()
        ;
    }
}
