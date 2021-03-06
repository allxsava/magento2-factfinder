<?php
/**
 * Attribute.php
 *
 * @category Mage
 * @package magento2
 * @author Flagbit Magento Team <magento@flagbit.de>
 * @copyright Copyright (c) 2015 Flagbit GmbH & Co. KG
 * @license GPL
 * @link http://www.flagbit.de
 */
namespace Flagbit\FACTFinder\Model\Export\Resource;

class Attribute
{
    /**
     * @var \Magento\Catalog\Model\Product\Attribute\Repository
     */
    protected $_attributeRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $_searchCriteriaBuilder;

    /**
     * @var array|null
     */
    protected $_searchableAttributes = null;

    /**
     * @var array|null
     */
    protected $_filterableAttributes = null;

    /**
     * @var array|null
     */
    protected $_numericalAttributes = null;

    /**
     * @var array
     */
    protected $_requiredAttributes = [
        'name',
        'description',
        'short_description',
        'price'
    ];

    protected $_attributeOptionLabels;


    /**
     * @param \Magento\Catalog\Model\Product\Attribute\Repository $attributeRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder        $searchCriteriaBuilder
     */
    public function __construct(
        \Magento\Catalog\Model\Product\Attribute\Repository $attributeRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder        $searchCriteriaBuilder
    ) {
        $this->_attributeRepository = $attributeRepository;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
    }


    /**
     * Get attributes used in search
     *
     * @return array
     */
    public function getSearchableAttributes()
    {
        if ($this->_searchableAttributes === null) {
            $this->_searchableAttributes = [];
            $searchCriteria = $this->_searchCriteriaBuilder->create();
            /** @var \Magento\Eav\Model\Entity\Attribute\AbstractAttribute $attribute */
            foreach ($this->_attributeRepository->getList($searchCriteria)->getItems() as $attribute) {
                if ($attribute->getIsSearchable()
                    && !in_array($attribute->getAttributeCode(), $this->_requiredAttributes)
                    && $attribute->getIsUserDefined()
                    && !in_array($attribute->getAttributeCode(), array_keys($this->getNumericalAttributes()))
                ) {
                    $this->_searchableAttributes[$attribute->getAttributeCode()] = $attribute;
                }
            }
        }

        return $this->_searchableAttributes;
    }


    /**
     * @return array
     */
    public function getAdditionalAttributeCodes()
    {
        return [];
    }


    /**
     * Retrieves attributes that should be exported separately
     *
     * @return array
     */
    public function getAdditionalAttributes()
    {
        return [];
    }


    /**
     * Get all attributes that can be used as filters
     *
     * @return array
     */
    public function getFilterableAttributes()
    {
        if ($this->_filterableAttributes === null) {
            $this->_filterableAttributes = [];
            $searchCriteria = $this->_searchCriteriaBuilder->create();
            /** @var \Magento\Eav\Model\Entity\Attribute\AbstractAttribute $attribute */
            foreach ($this->_attributeRepository->getList($searchCriteria)->getItems() as $attribute) {
                if ($attribute->getIsFilterable()
                    && !in_array($attribute->getAttributeCode(), $this->_requiredAttributes)
                    && !in_array($attribute->getAttributeCode(), array_keys($this->getSearchableAttributes()))
                ) {
                    $this->_filterableAttributes[$attribute->getAttributeCode()] = $attribute;
                }
            }
        }

        return $this->_filterableAttributes;
    }


    /**
     * Get all numerical attributes
     *
     * @return array
     */
    public function getNumericalAttributes()
    {
        if ($this->_numericalAttributes === null) {
            $this->_numericalAttributes = [];
            $searchCriteria = $this->_searchCriteriaBuilder->create();
            /** @var \Magento\Eav\Model\Entity\Attribute\AbstractAttribute $attribute */
            foreach ($this->_attributeRepository->getList($searchCriteria)->getItems() as $attribute) {
                if ($attribute->getBackendType() == 'decimal'
                    && $attribute->getIsFilterable()
                    && !in_array($attribute->getAttributeCode(), $this->_requiredAttributes)
                ) {
                    $this->_numericalAttributes[$attribute->getAttributeCode()] = $attribute;
                }
            }
        }

        return $this->_numericalAttributes;
    }


    /**
     * Retrieve attribute value from a product
     *
     * @param \Magento\Catalog\Model\Product                        $product
     * @param \Magento\Eav\Model\Entity\Attribute\AbstractAttribute $attribute
     *
     * @return array
     */
    public function getAttributeValue($product, $attribute)
    {
        $code = $attribute->getAttributeCode();
        $result = [];

        // select, multiselect
        if (in_array($attribute->getBackendType(), ['int', 'varchar'])) {
            if (!isset($this->_attributeOptionLabels[$code])) {
                $this->_loadAttributeValues($attribute);
            }

            $values = explode(',', $product->getData($code));
            foreach ($values as $value) {
                if (isset($this->_attributeOptionLabels[$code][$value])) {
                    $result[] = $this->_attributeOptionLabels[$code][$value];
                }
            }
        } else {
            $result[] = $product->getData($code);
        }

        return $result;
    }


    /**
     * Load all option values for an attribute to the cache variable
     *
     * @param \Magento\Eav\Model\Entity\Attribute\AbstractAttribute $attribute
     *
     * @return $this
     */
    protected function _loadAttributeValues($attribute)
    {
        $code = $attribute->getAttributeCode();
        $options = $attribute->getOptions();
        foreach ($options as $option) {
            if ($option->getValue()) {
                $this->_attributeOptionLabels[$code][$option->getValue()] = $option->getLabel();
            }
        }

        return $this;
    }


    /**
     * Get list of required attribute codes
     *
     * @return array
     */
    public function getRequiredAttributes()
    {
        return $this->_requiredAttributes;
    }


}