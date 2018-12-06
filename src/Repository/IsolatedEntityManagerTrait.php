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
     * @param object $entity
     *
     * @return void
     */
    private function flush($entity)
    {
        $this->_em->persist($entity);
        $this->_em->flush();
    }

    /**
     * @param object $entity
     *
     * @return void
     */
    public function save($entity)
    {
        $this->flush($entity);
    }

    /**
     * @param object $entity
     *
     * @return void
     */
    public function update($entity)
    {
        $this->flush($entity);
    }

    /**
     * @param object $entity
     */
    public function remove($entity)
    {
        $this->remove($entity);

        $this->_em->flush();
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
