<?php

namespace CultuurNet\UDB3\Role\ReadModel\Search;

class Results
{

    /**
     * @var int
     */
    private $itemsPerPage;

    /**
     * @var array
     */
    private $member;

    /**
     * @var int
     */
    private $totalItems;

    /**
     * @param int $itemsPerPage
     * @param array $member
     * @param int $totalItems
     */
    public function __construct($itemsPerPage, $member, $totalItems)
    {
        $this->itemsPerPage = $itemsPerPage;
        $this->member = $member;
        $this->totalItems = $totalItems;
    }

    /**
     * @return int
     */
    public function getItemsPerPage()
    {
        return $this->itemsPerPage;
    }

    /**
     * @return array
     */
    public function getMember()
    {
        return $this->member;
    }

    /**
     * @return int
     */
    public function getTotalItems()
    {
        return $this->totalItems;
    }
}
