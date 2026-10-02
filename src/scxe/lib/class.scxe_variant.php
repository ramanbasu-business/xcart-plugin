<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of class
 *
 * @author Raman
 */
class scxe_variant {

    private $debug = false;

    // process each product node in XML
    public function MatrixEntryPoint($productXML)
    {
        if (!isset($productXML->product_options)) {
            return false;
        }

        $sku = (string) $productXML->productcode;
        $p = \XLite\Core\Database::getRepo('\XLite\Model\Product')->findOneBy(array('sku' => $sku));
        if (!$p || !isset($p)) {
            return false;
        }

        $arrAttributeIds = array();
        $attrOptionArray = array();

        foreach ($productXML->product_options->product_option as $po):
            $attribute_name = (string) $po->class;
            $a = $this->_getMatrixAttribute($p, $attribute_name);
            if (!$a) {
                continue;
            }

            if (!isset($po->option)) {
                continue;
            }

            /* Sample data array
             *  $data = array();
              $data['value'] = array();
              $data['multiple'] = true;
              $data['value'][-2] = 'Red';
              $data['price'][-2] = 1.0;

              $data['value'][-1] = 'Green';
              $data['price'][-1] = 2.0;
             */
            $data = array();
            $data['value'] = array();
            $data['multiple'] = true;
            $index = count($po->option) * (-1);
            foreach ($po->option as $o):
                // process each option
                $option_name = addslashes((string) $o->option_name);
                $data['value'][$index] = $option_name;
                $data['price'][$index] = 0;
                $index++;

                $attrOptionArray[] = array(
                    'attribute_id' => $a->getId(),
                    'attribute_name' => $attribute_name,
                    'option_value' => $option_name);

            endforeach;
            $a->setAttributeValue($p, $data);

            $arrAttributeIds[$a->getId()] = $a->getId();
        endforeach;

        //$p->updateQuickData();
        \XLite\Core\Database::getEM()->flush();

        $this->_doActionCreateVariants($p, $arrAttributeIds);

        // STARTS VARIANT UPDATE
        //var_dump($attrOptionArray);

        foreach ($productXML->variants->variant as $variant):

            $variantArray = array();
            foreach ($variant->option as $vo):

                $variant_optionvalue = (string) $vo; // Green
                $attr_id = 0;
                // now find its related attr id from array
                foreach ($attrOptionArray as $arr_attr_id => $arr_data) {
                    if ($arr_data['option_value'] == $variant_optionvalue) {
                        $attr_id = $arr_data['attribute_id'];
                        break;
                    }
                }

                if ($attr_id > 0):
                    //$a = \XLite\Core\Database::getRepo('XLite\Model\Attribute')->find($attr_id);
                    $variantArray[$attr_id] = array(
                        'attribute_id' => $attr_id,
                        'option_value' => $variant_optionvalue);
                endif;

            endforeach;

            //if($this->debug) echo 'SKU ' . $_newProductCode . '<br />';
            //var_dump($variantArray);
            $variantObj = $this->_getVariant($p, $variantArray);
            if ($variantObj):
                // update from XML
                $_newProductCode = (string) $variant->productcode;
                $_newPrice = (double) $variant->price;
                $_newAvail = (int) $variant->avail;

                $variantObj->setSku($_newProductCode);
                $variantObj->setPrice($_newPrice);
                $variantObj->setAmount($_newAvail);

                $variantObj->setDefaultPrice(false);
                $variantObj->setDefaultAmount(false);

                $variantObj->setProduct($p);
                $p->addVariants($variantObj);
                \XLite\Core\Database::getEM()->persist($variantObj);
            //if($this->debug) var_dump($variantObj->getId());
            endif;
        endforeach;

        \XLite\Core\Database::getEM()->flush();
        return true;
    }

    public function updateAttributeWithSingleValue(\XLite\Model\Product $p, $attribute_name, $option_value)
    {
        $a = $this->_getMatrixAttribute($p, $attribute_name);
        if (!$a) {
            return false;
        }

        $data = array();
        $data['value'] = array();
        $data['multiple'] = false;

        $data['value'][0] = $option_value;
        $data['price'][0] = 0;
        $a->setAttributeValue($p, $data);

        //\XLite\Core\Database::getEM()->persist($a);
        //\XLite\Core\Database::getEM()->flush();
        return true;
    }

    private function _getMatrixAttribute(\XLite\Model\Product $p, $attrName)
    {
        $found = false;
        $repo = \XLite\Core\Database::getRepo('\XLite\Model\Attribute');
        //$multiAttrs = $p->getMultipleAttributes();
        $multiAttrs = $p->getAttributes();
        //$multiAttrs = \XLite\Core\Database::getRepo('XLite\Model\Attribute')->findAll();
        $attr = new XLite\Model\Attribute();
        foreach ($multiAttrs as $a):

            if ($a->getName() == $attrName) {
                $attr = $a;
                $found = true;
                break;
            }
            //echo ($a->getId() . $a->getName());
        endforeach;

        if ($found == true) {
            return $attr;
        }
        //echo 'not found';
        // Create new attribute(variant)
        $attr->setName($attrName);
        $attr->setType('S');
        $attr->setProduct($p);
        $p->addAttributes($attr);
        $repo->insert($attr);

        $p->updateQuickData();
        \XLite\Core\Database::getEM()->flush();
        return $attr;
    }

    // Need internally
    private function _updateVariantsAttributes(\XLite\Model\Product $product, $attrIds)
    {
        // original func: updateVariantsAttributes
        //$attr = \XLite\Core\Request::getInstance()->attr;
        //$product = $this->getProduct();

        if ($product->getVariantsAttributes()) {
            $product->getVariantsAttributes()->clear();
        }

        if ($attrIds) {
            $attributes = \XLite\Core\Database::getRepo('XLite\Model\Attribute')->findByIds($attrIds);
            foreach ($attributes as $a) {
                $product->addVariantsAttributes($a);
                $a->addVariantsProduct($product);
            }
        }

        $product->checkVariants();
        \XLite\Core\Database::getEM()->flush();
    }

    private function _doActionCreateVariants(\XLite\Model\Product $product, $arrAttributeIds)
    {
        // D:\xampp\htdocs\xcart514\var\run\classes\XLite\Module\XC\ProductVariants\Controller\Admin\Product.php
        $this->_updateVariantsAttributes($product, $arrAttributeIds);
        //echo 'variants exist';
        //$product = $this->getProduct();

        $variants = array();
        foreach ($product->getVariantsAttributes()->toArray() as $a) {
            $_variants = $variants;
            $variants = array();
            foreach ($a->getAttributeValue($product) as $attributeValue) {
                $val = array(array($attributeValue, $a->getType()));
                if ($_variants) {
                    foreach ($_variants as $v) {
                        $variants[] = array_merge($val, $v);
                    }
                }
                else {
                    $variants[] = $val;
                }
            }
        }

        if ($variants) {
            foreach ($variants as $attributeValues) {
                $variant = new \XLite\Module\XC\ProductVariants\Model\ProductVariant();
                foreach ($attributeValues as $attributeValue) {
                    $method = 'addAttributeValue' . $attributeValue[1];
                    $attributeValue = $attributeValue[0];
                    $variant->$method($attributeValue);
                    $attributeValue->addVariants($variant);
                }
                $variant->setProduct($product);
                $product->addVariants($variant);
                \XLite\Core\Database::getEM()->persist($variant);
            }
        }

        \XLite\Core\Database::getEM()->flush();

        $product->checkVariants();
        //('Variants have been created successfully');
        return true;
    }

    private function _getVariant(\XLite\Model\Product $p, $variantArray)
    {
        $tempIndex = 0;
        $values = array();

        foreach ($variantArray as $attr_id => $data) {
            $attribute = \XLite\Core\Database::getRepo('XLite\Model\Attribute')->find($attr_id);
            if (!$attribute) {
                continue;
            }

            $repo = \XLite\Core\Database::getRepo($attribute->getAttributeValueClass($attribute->getType()));

            $v = $data['option_value']; // Green
            $attributeOption = \XLite\Core\Database::getRepo('XLite\Model\AttributeOption')
                    ->findOneByNameAndAttribute($v, $attribute);

            if ($this->debug) {
                echo $v . ' ' . $attributeOption->getId();
            }

            $values[$tempIndex++] = $repo->findOneBy(
                    array(
                        'attribute' => $attribute,
                        'product' => $p,
                        'attribute_option' => $attributeOption,
                    )
            );
        }

        //var_dump($values);
        $variant = $p->getVariantByAttributeValues($values);
        if ($variant):
            if ($this->debug) {
                echo '<br />variant id: ' . $variant->getId() . '<br />';
            }
        endif;

        return $variant;

        ///////////////////////
        $attribute = $this->_getMatrixAttribute($p, 'Color');
        //var_dump($attribute->getName());
        $values[0] = $repo->findOneBy(
                array(
                    'attribute' => $attribute,
                    'product' => $p,
                    'attribute_option' => $attributeOption,
                )
        );

        $attribute = $this->_getMatrixAttribute($p, 'Size');
        $attributeOption = \XLite\Core\Database::getRepo('XLite\Model\AttributeOption')
                ->findOneByNameAndAttribute('XXL', $attribute);
        echo $attributeOption->getId();
        $values[1] = $repo->findOneBy(
                array(
                    'attribute' => $attribute,
                    'product' => $p,
                    'attribute_option' => $attributeOption,
                )
        );

        // var_dump($values);
        $variant = $p->getVariantByAttributeValues($values);
        var_dump($variant->getId());
    }

}
