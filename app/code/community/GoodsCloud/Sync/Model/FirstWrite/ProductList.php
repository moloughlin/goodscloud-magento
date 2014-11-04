<?php


class GoodsCloud_Sync_Model_FirstWrite_ProductList extends Mage_Core_Model_Flag implements Iterator, Countable
{
    protected $_flagCode;

    protected $indexedProductList = array();

    /**
     * @param string $code
     *
     * @return GoodsCloud_Sync_Model_FirstWrite_ProductList
     */
    public function setFlagCode($code)
    {
        $this->_flagCode = $code;
        $this->setData('flag_code', $code);
        return $this;
    }

    public function loadSelf()
    {
        $return = parent::loadSelf();
        $this->indexProductList();
        return $return;
    }

    /**
     * make sure to sync the indexed list and the saved list before saving
     *
     * @return Mage_Core_Model_Flag
     */
    protected function _beforeSave()
    {
        $this->setFlagData($this->getProductList());
        return parent::_beforeSave();
    }

    /**
     * set product list
     *
     * @param array $productList array with product ids
     */
    public function setProductList(array $productList)
    {
        $this->setFlagData($productList);
        $this->indexProductList();
    }

    /**
     * @return array product id list
     */
    public function getProductList()
    {
        return array_keys($this->indexedProductList);
    }

    /**
     * were all products be exported?
     *
     * when the import was finished, this should be an empty array
     * WARNING: flag must be loaded before this method run, if it were not loaded, wrong results may occur!
     */
    public function isFinished()
    {
        return (is_array($this->getFlagData()) && empty($this->getFlagData()));
    }

    /**
     * when one product was added, this should be an array with zero or more product ids
     *
     * @return bool
     */
    public function isFilled()
    {
        return is_array($this->getFlagData());
    }

    /**
     * @param int $id
     */
    public function removeProductId($id)
    {
        unset($this->indexedProductList[$id]);
        $this->setDataChanges(true);
    }

    /**
     * reverse key and value in the array, so the access time is faster, because the keys are a HashMap
     */
    private function indexProductList()
    {
        if (is_array($this->getFlagData())) {
            foreach ($this->getFlagData() as $productId) {
                $this->indexedProductList[$productId] = true;
            }
        }
    }

    public function current()
    {
        return key($this->indexedProductList);
    }

    public function next()
    {
        next($this->indexedProductList);
        return key($this->indexedProductList);
    }

    public function key()
    {
        return key($this->indexedProductList);
    }

    public function valid()
    {
        return (key($this->indexedProductList) !== null);
    }

    public function rewind()
    {
        return reset($this->indexedProductList);
    }

    /**
     * make sure to save the current state when the program crashes
     *
     * @throws Exception
     */
    function __destruct()
    {
        $this->save();
    }

    public function count()
    {
        return count($this->indexedProductList);
    }
}
