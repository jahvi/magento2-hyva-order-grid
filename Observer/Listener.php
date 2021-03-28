<?php
namespace MediaLounge\OrderGrid\Observer;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Hyva\Admin\Model\GridSourceType\RepositorySourceType\SearchCriteriaEventContainer;

class Listener implements \Magento\Framework\Event\ObserverInterface
{
    protected $request;
    protected $filterBuilder;
    protected $filterGroupBuilder;
    protected $searchCriteriaBuilder;

    public function __construct(
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterGroupBuilder $filterGroupBuilder
    ) {
        $this->request = $request;
        $this->filterBuilder = $filterBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var SearchCriteriaEventContainer $searchCriteriaContainer */
        $searchCriteriaContainer = $observer->getData('search_criteria_container');
        $params = $this->request->getParams();
        $customerName = isset($params['order-grid']['_filter']['customer_firstname'])
            ? $params['order-grid']['_filter']['customer_firstname']
            : null;
        $skus = isset($params['order-grid']['_filter']['items'])
            ? $params['order-grid']['_filter']['items']
            : null;

        $updatedSearchCriteria = $searchCriteriaContainer->getSearchCriteria();

        if ($customerName) {
            // Unset original customer_firstname filter
            $filterGroupsWithoutName = array_filter(
                $updatedSearchCriteria->getFilterGroups(),
                function ($filterGroup) {
                    foreach ($filterGroup->getFilters() as $filter) {
                        if ($filter->getField() === 'customer_firstname') {
                            return false;
                        }

                        return true;
                    }
                }
            );

            list($firstName, $lastName) = explode(' ', $customerName, 2);

            // If more than 2 words search first name and last name independently
            if ($firstName && $lastName) {
                $customerNameFilter = $this->filterBuilder
                    ->setField('customer_firstname')
                    ->setValue("%$firstName%")
                    ->setConditionType('like')
                    ->create();

                $customerLastnameFilter = $this->filterBuilder
                    ->setField('customer_lastname')
                    ->setValue("%$lastName%")
                    ->setConditionType('like')
                    ->create();

                $customerNameFilterGroup = $this->filterGroupBuilder
                    ->addFilter($customerNameFilter)
                    ->create();
                $customerLastnameFilterGroup = $this->filterGroupBuilder
                    ->addFilter($customerLastnameFilter)
                    ->create();

                $filterGroupsWithoutName[] = $customerNameFilterGroup;
                $filterGroupsWithoutName[] = $customerLastnameFilterGroup;
                // If only one word use the same for fist name and last name
            } else {
                $customerNameFilter = $this->filterBuilder
                    ->setField('customer_firstname')
                    ->setValue("%$customerName%")
                    ->setConditionType('like')
                    ->create();

                $customerLastnameFilter = $this->filterBuilder
                    ->setField('customer_lastname')
                    ->setValue("%$customerName%")
                    ->setConditionType('like')
                    ->create();

                $customerNameFilterGroup = $this->filterGroupBuilder
                    ->addFilter($customerNameFilter)
                    ->addFilter($customerLastnameFilter)
                    ->create();

                $filterGroupsWithoutName[] = $customerNameFilterGroup;
            }

            $updatedSearchCriteria->setFilterGroups($filterGroupsWithoutName);
        }

        if ($skus) {
            // Unset original items filter
            $filterGroupsWithoutSkus = array_filter(
                $updatedSearchCriteria->getFilterGroups(),
                function ($filterGroup) {
                    foreach ($filterGroup->getFilters() as $filter) {
                        if ($filter->getField() === 'items') {
                            return false;
                        }

                        return true;
                    }
                }
            );

            $skusFilter = $this->filterBuilder
                ->setField('soi.skus')
                ->setValue("%$skus%")
                ->setConditionType('like')
                ->create();

            $skusFilterGroup = $this->filterGroupBuilder->addFilter($skusFilter)->create();

            $filterGroupsWithoutSkus[] = $skusFilterGroup;

            $updatedSearchCriteria->setFilterGroups($filterGroupsWithoutSkus);
        }

        return $updatedSearchCriteria;
    }
}
