<?php

namespace Doofinder\Feed\Test\Unit\Model\Config\Source\Feed;

/**
 * Test class for \Doofinder\Feed\Model\Config\Source\Feed\Attributes
 */
class AttributesTest extends \Magento\Framework\TestFramework\Unit\BaseTestCase
{
    /**
     * @var Magento\Eav\Model\Config
     */
    private $eavConfig;

    /**
     * @var \Magento\Framework\Escaper
     */
    private $escaper;

    /**
     * @var \Magento\Eav\Model\Entity\Type
     */
    private $entityType;

    /**
     * @var \Doofinder\Feed\Model\Config\Source\Feed\Attributes
     */
    private $model;

    /**
     * Doofinder directives
     * @var array
     */
    private $directives;

    /**
     * Set up test
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->directives = [
            'df_id' => 'Doofinder: Product Id',
            'df_availability' => 'Doofinder: Product Availability',
            'df_currency' => 'Doofinder: Product Currency',
            'df_regular_price' => 'Doofinder: Product Regular Price',
            'df_sale_price' => 'Doofinder: Product Sale Price',
        ];

        $this->eavConfig = $this->getMockBuilder(\Magento\Eav\Model\Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->escaper = $this->getMockBuilder(\Magento\Framework\Escaper::class)
            ->setMethods(null)
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityType = $this->getMockBuilder(\Magento\Eav\Model\Entity\Type::class)
            ->disableOriginalConstructor()
            ->getMock();

        $eavAttribute = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Eav\Attribute::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAttributeLabel', 'getAttributeCode'])
            ->getMock();

        $eavAttribute->expects($this->any())
            ->method('getAttributeLabel')
            ->willReturn('attr label');

        $eavAttribute->expects($this->any())
            ->method('getAttributeCode')
            ->willReturn('attr code');

        $attrCollection = $this->objectManager->getCollectionMock(
            \Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection::class,
            [$eavAttribute]
        );

        $this->entityType->expects($this->once())
            ->method('getAttributeCollection')
            ->willReturn($attrCollection);

        $this->eavConfig->expects($this->once())
            ->method('getEntityType')
            ->with(\Magento\Catalog\Model\Product::ENTITY)
            ->willReturn($this->entityType);

        $this->model = $this->objectManager->getObject(
            \Doofinder\Feed\Model\Config\Source\Feed\Attributes::class,
            [
                'eavConfig' => $this->eavConfig,
                'escaper' => $this->escaper
            ]
        );
    }

    /**
     * Test toOptionArray() method
     *
     * @return void
     */
    public function testToOptionArray()
    {
        $expected = $this->directives + [
            'attr code' => 'Attribute: attr code'
        ];

        $this->assertEquals($expected, $this->model->toOptionArray());
    }

    /**
     * Test getAllAttributes() method
     *
     * @return void
     */
    public function testGetAllAttributes()
    {
        $expected = $this->directives + [
            'attr code' => 'Attribute: attr code'
        ];

        $this->assertEquals($expected, $this->model->getAllAttributes());
    }
}
