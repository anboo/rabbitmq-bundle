<?php

namespace Anboo\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BaseController extends Controller
{
    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @required
     *
     * @param SerializerInterface $serializer
     */
    public function setSerializer(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @required
     *
     * @param ValidatorInterface $validator
     */
    public function setValidator(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @required
     *
     * @param TranslatorInterface $translator
     */
    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param mixed $data
     * @param int   $status
     * @param array $serializationGroups
     * @param bool  $enableMaxDepth
     *
     * @return JsonResponse
     */
    protected function handleResponse($data, $status = Response::HTTP_OK, $serializationGroups = false, $enableMaxDepth = false)
    {
        $body = $this->getBody($data, $serializationGroups, $enableMaxDepth);

        return new JsonResponse($body, $status, [], true);
    }

    /**
     * @param mixed $data
     * @param array $serializationGroups
     * @param bool  $enableMaxDepth
     *
     * @return string
     */
    protected function getBody($data, $serializationGroups = false, $enableMaxDepth = false)
    {
        $serializationGroups = $this->processSerializationGroups($serializationGroups);

        return null !== $data ? $this->serialize($data, (array) $serializationGroups, $enableMaxDepth) : '';
    }

    /**
     * @param mixed $data                Data
     * @param array $serializationGroups Context
     * @param bool  $enableMaxDepth
     *
     * @return string
     */
    protected function serialize($data, array $serializationGroups = [], $enableMaxDepth = false)
    {
        $options = ['groups' => $serializationGroups];
        if ($enableMaxDepth) {
            $options['enable_max_depth'] = true;
        }

        return $this->serializer->serialize($data, 'json', $options);
    }

    /**
     * @param array $serializationGroups
     *
     * @return mixed
     */
    protected function processSerializationGroups($serializationGroups)
    {
        return $serializationGroups;
    }

    /**
     * @param Request $request
     * @param         $entityClass
     * @param null    $entityObject
     * @param array   $groups
     *
     * @return object
     */
    public function getEntityFromRequestTo(Request $request, $entityClass, $entityObject = null, $groups = false)
    {
        if ($groups) {
            $serializerContext = ['groups' => $groups];
        }

        if ($entityObject) {
            $serializerContext['object_to_populate'] = $entityObject;
        }

        return $this->serializer->deserialize($request->getContent(), $entityClass, 'json', $serializerContext);
    }

    /**
     * @param object $entity
     * @return ConstraintViolationListInterface
     */
    public function validate($entity)
    {
        return $this->validator->validate($entity);
    }

    /**
     * @param ConstraintViolationListInterface $constraintViolationList
     *
     * @return JsonResponse
     */
    public function handleErrorValidationResponse(ConstraintViolationListInterface $constraintViolationList)
    {
        $formatErrors = [];

        /** @var ConstraintViolationInterface $violation */
        foreach ($constraintViolationList as $violation) {
            $formatErrors[$violation->getPropertyPath()] = $this->translator->trans($violation->getMessage());
        }

        return $this->handleErrorResponse($formatErrors);
    }

    /**
     * @param array $formatErrors
     * @return JsonResponse
     */
    public function handleErrorResponse(array $formatErrors)
    {
        return new JsonResponse(['errors' => $formatErrors], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}