<?php

declare(strict_types=1);

namespace App\Controller;

use App\Api\ApiProblem;
use App\Api\ApiProblemException;
use App\Entity\User;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * Class BaseController.
 *
 * @method User getUser()
 */
class BaseController extends AbstractController
{
    const PAGINATION_PER_PAGE = 10;
    const PAGINATION_MAX_PER_PAGE = 50;

    /**
     * Populates the pagination with default values from the request.
     * Performs checks to ensure we have minimum and maximum values.
     * E.g. items per page should be greater than 0 and less than a max (set as a constant in this class).
     *
     * If 'sort' is not in the request, default parameters are used.
     *
     * @param $query
     */
    public function populatePaginationData(Request $request, PaginatorInterface $paginator, QueryBuilder $query, array $defaultParams = []): PaginationInterface
    {
        $itemsPerPage = $request->query->getInt('per_page', self::PAGINATION_PER_PAGE);

        if ($itemsPerPage < 1) {
            $itemsPerPage = self::PAGINATION_PER_PAGE;
        }

        if ($itemsPerPage > self::PAGINATION_MAX_PER_PAGE) {
            $itemsPerPage = self::PAGINATION_MAX_PER_PAGE;
        }

        if (!$request->query->has('sort')) {
            if (array_key_exists('sort', $defaultParams) &&
                array_key_exists('direction', $defaultParams)) {
                $query->addOrderBy($defaultParams['sort'], $defaultParams['direction']);
            }
        }

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            $itemsPerPage,
        );

        if (!$request->query->has('sort')) {
            foreach ($defaultParams as $key => $value) {
                $pagination->setParam($key, $value);
            }
        }

        return $pagination;
    }

    /**
     * getJsonBody attempts to decode the request content into an associative array via json_decode.
     * If the result is null, and no default is provided, a 400 error is thrown with a code indicating an invalid body.
     * If the result is null, and a non-null default is provided, the default is returned.
     *
     * @param Request $request
     * @param array|null $default
     * @return array
     */
    public function getJsonBody(Request $request, array $default = null): array
    {
        $data = json_decode($request->getContent(), true);
        if (is_null($data)) {
            if (!is_null($default)) {
                return $default;
            }

            throw new ApiProblemException(
                new ApiProblem(Response::HTTP_BAD_REQUEST, ApiProblem::TYPE_INVALID_REQUEST_BODY)
            );
        }

        return $data;
    }

    public function now(): DateTime
    {
        return new DateTime('now', new DateTimeZone('UTC'));
    }

    public function jsonOk(): JsonResponse
    {
        return $this->json([], Response::HTTP_OK);
    }

    public function jsonNoContent(): JsonResponse
    {
        return $this->json([], Response::HTTP_NO_CONTENT);
    }

    /**
     * Changes the serializer so that nulls are not output in the response, they are removed.
     *
     * @param $data
     * @param int $status
     * @param array $headers
     * @param array $context
     * @return JsonResponse
     */
    public function jsonNoNulls($data, int $status = 200, array $headers = [], array $context = []): JsonResponse
    {
        return $this->json($data, $status, $headers, array_merge([AbstractObjectNormalizer::SKIP_NULL_VALUES => true, $context]));
    }
}
