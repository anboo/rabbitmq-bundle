<?php
/**
 * Created by PhpStorm.
 * User: anboo
 * Date: 06.12.18
 * Time: 7:46
 */

namespace Anboo\ApiBundle\Repository;

/**
 * Trait IsolationEntityManagerTrait
 */
trait IsolatedEntityManagerTrait
{
    /**
     * @return void
     */
    public function flush()
    {
        $this->_em->flush();
    }

    /**
     * @param object $entity
     *
     * @return void
     */
    private function persist($entity)
    {
        $this->_em->persist($entity);
    }

    /**
     * @param object $entity
     * @param boolean $flush
     *
     * @return void
     */
    public function save($entity, $flush = true)
    {
        $this->persist($entity);
        if ($flush) {
            $this->flush();
        }
    }

    /**
     * @param object $entity
     *
     * @return void
     */
    public function update($entity)
    {
        $this->persist($entity);
        $this->flush();
    }

    /**
     * @param object $entity
     */
    public function remove($entity)
    {
        $this->remove($entity);

        $this->flush();
    }

    /**
     * @return mixed
     */
    public function removeAll()
    {
        return $this->createQueryBuilder('g')
            ->delete()
            ->getQuery()
            ->execute();
    }

    /**
     * @return mixed
     */
    public function getManager()
    {
        return $this->_em;
    }
}
