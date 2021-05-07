<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

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

    public function now(): DateTime {
        return new DateTime('now', new DateTimeZone('UTC'));
    }
}
