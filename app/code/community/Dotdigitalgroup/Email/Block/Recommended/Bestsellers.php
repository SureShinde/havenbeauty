<?php

class Dotdigitalgroup_Email_Block_Recommended_Bestsellers extends Mage_Core_Block_Template
{

    /**
	 * Prepare layout.
	 * @return Mage_Core_Block_Abstract|void
	 */
	protected function _prepareLayout()
    {
        if ($root = $this->getLayout()->getBlock('root')) {
            $root->setTemplate('page/blank.phtml');
        }
    }

	/**
	 * Get product collection.
	 * @return array
	 * @throws Exception
	 */
	public function getLoadedProductCollection()
    {
        $mode = $this->getRequest()->getActionName();
        $limit  = Mage::helper('ddg/recommended')->getDisplayLimitByMode($mode);
        $from  =  Mage::helper('ddg/recommended')->getTimeFromConfig($mode);
	    $to = Zend_Date::now()->toString(Zend_Date::ISO_8601);

	    $productCollection = Mage::getResourceModel('reports/product_collection')
		    ->addAttributeToSelect('*')
		    ->addOrderedQty($from, $to)
            ->setOrder('ordered_qty', 'desc')
	        ->setPageSize($limit);

	    Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($productCollection);
	    $productCollection->addAttributeToFilter('is_saleable', TRUE);

        //filter collection by category by category_id
        if($cat_id = Mage::app()->getRequest()->getParam('category_id')){
            $category = Mage::getModel('catalog/category')->load($cat_id);
            if($category->getId()){
                $productCollection->getSelect()
                    ->joinLeft(
                        array("ccpi" => 'catalog_category_product_index'),
                        "e.entity_id  = ccpi.product_id",
                        array("category_id")
                    )
                    ->where('ccpi.category_id =?',  $cat_id);
            }else{
                Mage::helper('ddg')->log('Best seller. Category id '. $cat_id . ' is invalid. It does not exist.');
            }
        }

        //filter collection by category by category_name
        if($cat_name = Mage::app()->getRequest()->getParam('category_name')){
            $category = Mage::getModel('catalog/category')->loadByAttribute('name', $cat_name);
            if($category){
                $productCollection->getSelect()
                    ->joinLeft(
                        array("ccpi" => 'catalog_category_product_index'),
                        "e.entity_id  = ccpi.product_id",
                        array("category_id")
                    )
                    ->where('ccpi.category_id =?',  $category->getId());
            }else{
                Mage::helper('ddg')->log('Best seller. Category name '. $cat_name .' is invalid. It does not exist.');
            }
        }
	    return $productCollection;
    }

	/**
	 * Display type mode.
	 *
	 * @return mixed|string
	 */
	public function getMode()
    {
        return Mage::helper('ddg/recommended')->getDisplayType();

    }

	/**
	 * Price html.
	 * @param $product
	 *
	 * @return string
	 */
	public function getPriceHtml($product)
    {
        $this->setTemplate('connector/product/price.phtml');
        $this->setProduct($product);
        return $this->toHtml();
    }

}