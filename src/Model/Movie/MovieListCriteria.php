<?php

namespace App\Model\Movie;

class MovieListCriteria
{
    public const DEFAULT_ITEMS_PER_PAGE = 10;
    public const DEFAULT_PAGE = 1;
    public const DEFAULT_SORT_BY = 'name';
    public const DEFAULT_SORT_ORDER = 'ASC';

    public ?string $nameCriteria;
    public ?string $genreCriteria;
    public int $itemsPerPage;
    public int $page;
    public string $orderByField;
    public string $sortOrder;

    public function __construct(
        ?string $nameCriteria = null,
        ?string $genreCriteria = null,
        int $itemsPerPage,
        int $page,
        string $orderByField,
        string $sortOrder
    ) {
        $this->nameCriteria = $nameCriteria;
        $this->genreCriteria = $genreCriteria;
        $this->itemsPerPage = $itemsPerPage;
        $this->page = $page;
        $this->orderByField = $orderByField;
        $this->sortOrder = $sortOrder;
    }

    public function getNameCriteria(): ?string
    {
        return $this->nameCriteria;
    }

    public function getGenreCriteria(): ?string
    {
        return $this->genreCriteria;
    }

    public function getOrderByField(): string
    {
        return $this->orderByField;
    }

    public function getSortOrder(): string
    {
        return $this->sortOrder;
    }

    public function getItemsPerPage(): int
    {
        return $this->itemsPerPage;
    }

    public function getPage(): int
    {
        return $this->page;
    }
}
