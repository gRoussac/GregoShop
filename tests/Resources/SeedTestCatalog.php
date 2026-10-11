<?php

declare(strict_types=1);

namespace Tests\Resources;

use AttributeGroup;
use Combination;
use Configuration;
use Product;
use ProductAttribute;
use RuntimeException;
use StockAvailable;
use Tools;

/**
 * Minimal catalog for integration/behaviour/API test DB dumps.
 * Not used by the web installer (empty-shop).
 */
final class SeedTestCatalog
{
    public static function seed(): void
    {
        $idLang = (int) Configuration::get('PS_LANG_DEFAULT');
        $idTaxRulesGroup = (int) Configuration::get('PS_TAX_RULES_GROUP_DEFAULT') ?: 1;

        for ($i = 1; $i <= 20; ++$i) {
            $product = new Product(null, false, $idLang);
            $product->name = 'Test product ' . $i;
            $product->link_rewrite = 'test-product-' . $i;
            $product->reference = 'demo_' . $i;
            $product->price = 10 + $i;
            $product->active = 1;
            $product->id_tax_rules_group = $idTaxRulesGroup;
            $product->redirect_type = '404';
            $product->visibility = 'both';
            if (!$product->add()) {
                throw new RuntimeException('Failed to create test product ' . $i);
            }
            StockAvailable::setQuantity((int) $product->id, 0, 100);
        }

        self::addCombinationOnProductOne($idLang);
    }

    private static function addCombinationOnProductOne(int $idLang): void
    {
        $group = new AttributeGroup();
        $group->is_color_group = false;
        $group->group_type = 'select';
        $group->name = [$idLang => 'Size'];
        $group->public_name = [$idLang => 'Size'];
        if (!$group->add()) {
            throw new RuntimeException('Failed to create attribute group for test catalog');
        }

        $attribute = new ProductAttribute();
        $attribute->id_attribute_group = (int) $group->id;
        $attribute->name = [$idLang => 'S'];
        if (!$attribute->add()) {
            throw new RuntimeException('Failed to create attribute for test catalog');
        }

        $combination = new Combination();
        $combination->id_product = 1;
        $combination->reference = 'demo_1_s';
        $combination->price = 0;
        $combination->quantity = 50;
        if (!$combination->add()) {
            throw new RuntimeException('Failed to create combination for product 1');
        }
        $combination->setAttributes([(int) $attribute->id]);
        StockAvailable::setQuantity(1, (int) $combination->id, 50);
        Tools::clearCache();
    }
}
