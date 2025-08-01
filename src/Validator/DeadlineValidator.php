<?php

namespace App\Validator;

use App\Entity\Projet;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class DeadlineValidator extends ConstraintValidator
{
    public function validate($object, Constraint $constraint)
    {
        if (!$object instanceof Projet) {
            return;
        }

        $deadline = $object->getDeadline();
        $etat = $object->getSuivi();

        if ($etat == 'en attente' && $deadline < new \DateTime()) {
            $this->context->buildViolation($constraint->message)
                ->atPath('deadline')
                ->addViolation();
        }
    }
}
