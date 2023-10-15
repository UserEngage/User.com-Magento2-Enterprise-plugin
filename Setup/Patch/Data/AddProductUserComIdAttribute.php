<?php

namespace Usercom\Analytics\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Attribute;
use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Catalog\Setup\Patch\Data\UpdateProductAttributes;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;


/**
 * Class AddUserComUserIdAttribute
 *
 * @author Piotr Niewczas <piotr.niewczas@movecloser.pl>
 */
class AddProductUserComIdAttribute implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var CategorySetupFactory
     */
    private $catalogSetupFactory;

    /**
     * @var Attribute
     */
    private $attributeResource;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CategorySetupFactory $catalogSetupFactory
     * @param Attribute $attributeResource
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CategorySetupFactory $catalogSetupFactory,
        Attribute $attributeResource
    ) {
        $this->moduleDataSetup     = $moduleDataSetup;
        $this->catalogSetupFactory = $catalogSetupFactory;
        $this->attributeResource   = $attributeResource;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [
            UpdateProductAttributes::class,
        ];
    }

    /**
     * @inheritdoc
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function apply()
    {
        $catalogSetup = $this->catalogSetupFactory->create(['setup' => $this->moduleDataSetup]);

        /**
         * Add attribute
         */
        $eavSetupAttribute = $catalogSetup->addAttribute(
            Product::ENTITY,
            'usercom_product_id',
            [
                'type'          => 'varchar',
                'label'         => 'UserCom Product ID',
                'input'         => 'text',
                'backend_type'  => 'varchar',
                'source'        => null,
                'position'      => 102,
                'required'      => false,
                'system'        => false,
                'default'       => null,
                'used_in_forms' => ['adminhtml_catalog_product']
            ]
        );

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
