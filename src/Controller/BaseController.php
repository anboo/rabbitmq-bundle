<?php

namespace Anboo\ApiBundle\Controller;

use Anboo\ApiBundle\Swagger\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BaseController extends AbstractController
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
     * @param mixed $data
     * @param int   $status
     * @param array $serializationGroups
     * @param bool  $enableMaxDepth
     *
     * @return JsonResponse
     */
    protected function handleResponse($data, $status = Response::HTTP_OK, array $serializationGroups = [], $enableMaxDepth = false)
    {
        return $this->createJsonResponse(
            ApiResponse::createSuccessfullyResponse(
                $this->getRequestId(),
                $data
            ),
            $status,
            $serializationGroups
        );
    }

    /**
     * @param mixed $data
     * @param array $serializationGroups
     * @param bool  $enableMaxDepth
     *
     * @return string
     */
    protected function getBody($data, array $serializationGroups = [], $enableMaxDepth = false)
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
        $serializationGroups = $this->processSerializationGroups($serializationGroups);
        $options = $serializationGroups ? ['groups' => $serializationGroups]: [];

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
        if ($serializationGroups) {
            $serializationGroups[] = 'default';
        }
        
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
    public function getEntityFromRequestTo(Request $request, $entityClass,  $entityObject = null, $groups = [])
    {
        if ($groups) {
            $serializerContext = ['groups' => $groups];
        } else {
            $serializerContext = [];
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
    public function handleErrorResponse(ConstraintViolationListInterface $constraintViolationList)
    {
        $formatErrors = [];

        /** @var ConstraintViolationInterface $violation */
        foreach ($constraintViolationList as $violation) {
            $formatErrors[$violation->getPropertyPath()] = $violation->getMessage();
        }

        return new JsonResponse(['errors' => $formatErrors], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @param array $errors
     * @return JsonResponse
     */
    public function createErrorResponse(array $errors)
    {
        $response = ApiResponse::createErrorResponse(
            $this->getRequestId(),
            $errors
        );

        return $this->createJsonResponse($response, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @param ApiResponse $apiResponse
     * @param integer $code
     * @param array $serializationGroups
     * @return JsonResponse
     */
    public function createJsonResponse(ApiResponse $apiResponse, $code = Response::HTTP_OK, array $serializationGroups = [])
    {
        $data = [
            'status' => $apiResponse->getStatus(),
            'response' => $apiResponse->getData(),
            'errors' => $apiResponse->getErrors(),
            'requestId' => $apiResponse->getRequestId(),
        ];

        $body = $this->serialize($data, $serializationGroups);

        return new JsonResponse($body, $code, [], true);
    }

    /**
     * @return string
     */
    private function getRequestId() : string
    {
        if ($request = $this->get('request_stack')->getCurrentRequest()) {
            return $request->headers->get('X-Request-Id') ?? '';
        }

        return '';
    }
}