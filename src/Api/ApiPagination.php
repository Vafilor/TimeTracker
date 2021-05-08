<?php

declare(strict_types=1);

namespace App\Api;

use Knp\Component\Pager\Pagination\PaginationInterface;

class ApiPagination
{
    public int $totalCount;
    public int $count;
    public int $page;
    public int $perPage;
    public int $totalPages;
    public array $data;

    public static function fromPagination(PaginationInterface $pagination, array $data): ApiPagination
    {
        $result = new ApiPagination();

        $result->totalCount = $pagination->getTotalItemCount();
        $result->count = $pagination->count();
        $result->page = $pagination->getCurrentPageNumber();
        $result->perPage = $pagination->getItemNumberPerPage();

        $result->totalPages = intval(max(ceil($pagination->getTotalItemCount() / $pagination->getItemNumberPerPage()), 1));
        $result->data = $data;

        return $result;
    }

    public function __construct() {
        $this->totalCount = 0;
        $this->count = 0;
        $this->page = 0;
        $this->perPage = 0;
        $this->totalPages = 0;
        $this->data = [];
    }
}