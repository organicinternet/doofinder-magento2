<?php

namespace Doofinder\Feed\Model\Generator\Map\Product;

use \Doofinder\Feed\Model\Generator\Map\Product;

/**
 * Class Configurable
 *
 * @package Doofinder\Feed\Model\Generator\Map\Product
 */
class Configurable extends Product
{
    /**
     * @var \Doofinder\Feed\Model\Generator\Map\Product\AssociateFactory
     */
    protected $_mapFactory;

    /**
     * @var boolean
     */
    protected $_grouped;

    /**
     * @var \Doofinder\Feed\Model\Generator\Map[]
     */
    protected $_associatesMaps = [];

    /**
     * Class constructor
     *
     * @param \Doofinder\Feed\Model\Generator\Map\Product\AssociateFactory $mapFactory
     * @param \Doofinder\Feed\Helper\Product $helper
     * @param \Doofinder\Feed\Model\Generator\Item $item
     * @param \Magento\Tax\Model\Config $taxConfig
     * @param array $data = []
     */
    public function __construct(
        \Doofinder\Feed\Model\Generator\Map\Product\AssociateFactory $mapFactory,
        \Doofinder\Feed\Helper\Product $helper,
        \Doofinder\Feed\Model\Generator\Item $item,
        \Magento\Tax\Model\Config $taxConfig,
        array $data = []
    ) {
        $this->_mapFactory = $mapFactory;
        parent::__construct($helper, $item, $taxConfig, $data);
    }

    /**
     * Handle associate items skip before basic mapping
     */
    public function before()
    {
        $this->_grouped = !$this->getSplitConfigurableProducts();

        if ($this->_grouped) {
            $associates = $this->_item->getAssociates();
            /**
            * Set all items as skipped
            * @notice Compatible with PHP5.3+
            */
            array_walk($associates, function ($associate) {
                $associate->skip();
            });
        }
    }

    /**
     * Get associate data if value not exists in parent item
     *
     * @param string $field
     * @return mixed
     */
    public function get($field)
    {
        // Only merge associated items values if option is enabled
        if ($this->_grouped) {
            switch ($field) {
                case 'df_availability':
                    return $this->getAssociatesAvailability();
            }

            return $this->getGroupedField($field);
        }

        return parent::get($field);
    }

    /**
     * Map availability of associated items
     *
     * @return mixed
     */
    protected function getAssociatesAvailability()
    {
        $value = parent::get('df_availability');

        // Return out of stock if configurable product is out of stock
        if ($value == $this->_helper->getOutOfStockLabel()) {
            return $value;
        }

        // Return out of stock label if all associated products are out of stock
        $associatesValues = $this->getAssociatesValues('df_availability');
        if (array_unique($associatesValues) == [$this->_helper->getOutOfStockLabel()]) {
            return $this->_helper->getOutOfStockLabel();
        }

        // Return in stock otherwise
        return $value;
    }

    /**
     * Map field of merged values of associated items and configurable product
     *
     * @param string $field
     * @return mixed
     */
    protected function getGroupedField($field)
    {
        // Get configurable product value
        $value = parent::get($field);

        // Get values of associated items
        $associatesValues = $this->getAssociatesValues($field);

        if (!is_array($value)) {
            $value = [$value];
        }

        $value = array_merge($value, $associatesValues);

        // Remove duplicates
        $value = array_values(array_unique($value));

        // Filter out empty values (0 is not an empty value)
        $value = array_filter($value, function ($item) {
            return $item || $item === 0;
        });

        // Remove array if value is single
        if (count($value) == 1) {
            $value = $value[0];
        }

        return $value;
    }

    /**
     * Get value of associated item
     *
     * @param string $field
     * @return mixed
     */
    protected function getAssociatesValues($field)
    {
        $associatesValues = [];

        foreach ($this->_item->getAssociates() as $associate) {
            $associatesValues[] = $this->getAssociateMap($associate)->get($field);
        }

        /**
         * Flatten array recursively
         */
        $flattened = array();
        array_walk_recursive($associatesValues, function ($item) use (&$flattened) {
            $flattened[] = $item;
        });
        $associatesValues = $flattened;

        /**
         * Filter out null values
         * @notice Compatible with PHP5.3+
         */
        return array_filter($associatesValues, function ($value) {
            return $value !== null;
        });
    }

    /**
     * Get value of associated item map
     *
     * @param \Doofinder\Feed\Model\Generator\Item $associate
     * @return \Doofinder\Feed\Model\Generator\Map
     */
    protected function getAssociateMap(\Doofinder\Feed\Model\Generator\Item $associate)
    {
        $hash = spl_object_hash($associate);

        if (!isset($this->_associatesMaps[$hash])) {
            $this->_associatesMaps[$hash] = $this->_mapFactory->create([
                'item' => $associate,
                'data' => $this->getData(),
            ]);
        }

        return $this->_associatesMaps[$hash];
    }
}