<?php
namespace MediaLounge\OrderGrid\Model\CollectionProcessor\JoinProcessor;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Api\SearchCriteria\CollectionProcessor\JoinProcessor\CustomJoinInterface;

class OrderSkus implements CustomJoinInterface
{
    public function apply(AbstractDb $collection)
    {
        $collection->getSelect()->joinLeft(
            [
                'soi' => $collection
                    ->getConnection()
                    ->select()
                    ->from(
                        ['order_items' => 'sales_order_item'],
                        [
                            'order_id',
                            'GROUP_CONCAT(`sku` SEPARATOR ", ") AS skus',
                        ]
                    )
                    ->group('order_items.order_id'),
            ],
            'soi.order_id = main_table.entity_id',
            'skus'
        );

        return true;
    }
}
