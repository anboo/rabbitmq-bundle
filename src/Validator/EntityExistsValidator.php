<?php
/**
 * Created by PhpStorm.
 * User: anboo
 * Date: 29.12.18
 * Time: 12:13
 */

namespace Anboo\ApiBundle\Validator;

use Anboo\ApiBundle\Validator\Constraint\EntityExistsConstraint;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class EntityExistsValidator extends ConstraintValidator
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /**
     * EntityExistsValidator constructor.
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param mixed $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof EntityExistsConstraint) {
            throw new UnexpectedTypeException($constraint, EntityExistsConstraint::class);
        }

        if (null === $value) {
            return null;
        }

        if (!$this->entityManager->find($constraint->entityClass, $value)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ string }}', $value)
                ->addViolation();
        }
    }
}