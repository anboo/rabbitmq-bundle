<?php
/**
 * Created by PhpStorm.
 * User: anboo
 * Date: 29.12.18
 * Time: 12:12
 */

namespace Anboo\ApiBundle\Validator\Constraint;

use Anboo\ApiBundle\Validator\EntityExistsValidator;
use Symfony\Component\Validator\Constraint;

/**
 * Class EntityExistsConstraints
 * @Annotation
 */
class EntityExistsConstraint extends Constraint
{
    public $message = '';

    public $entityClass;

    public $field;

    /**
     * @return string
     */
    public function validatedBy()
    {
        return EntityExistsValidator::class;
    }
}