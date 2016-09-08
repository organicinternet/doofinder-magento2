<?php

namespace Doofinder\Feed\Model\Generator\Map\Product;

use \Doofinder\Feed\Model\Generator\Map\Product;

class Associate extends Product
{
    /**
     * Get value
     *
     * @param string $field
     * @return mixed
     */
    public function get($field)
    {
        switch ($field) {
            case 'url_key':
                if ($this->_context->isVisibleInSiteVisibility()) {
                    break;
                }

            case 'df_id':
            case 'name':
            case 'description':
            case 'price':
            case 'image':
            case 'availability':
            case 'type_id':
                return;
        }

        return parent::get($field);
    }
}
