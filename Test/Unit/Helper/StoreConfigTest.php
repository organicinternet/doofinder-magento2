<?php

namespace Doofinder\Feed\Test\Unit\Helper;

/**
 * Test class for \Doofinder\Feed\Helper\StoreConfig
 */
class StoreConfigTest extends \Magento\Framework\TestFramework\Unit\BaseTestCase
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManager
     */
    private $storeManager;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var \Magento\Store\Api\Data\StoreInterface
     */
    private $storeInterface;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    private $request;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    private $storeWebsiteRelation;

    /**
     * @var \Doofinder\Feed\Helper\StoreConfig
     */
    private $helper;

    /**
     * @var \Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory
     */
    private $configCollectionFactory;

    /**
     * @var \Magento\Config\Model\ResourceModel\Config\Data\Collection
     */
    private $configCollection;

    /**
     * @var \Magento\Framework\App\Config\Value
     */
    private $configValue;

    /**
     * Set up test
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->scopeConfig = $this->getMockBuilder(\Magento\Framework\App\Config\ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeManager = $this->getMockBuilder(\Magento\Store\Model\StoreManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeInterface = $this->getMockBuilder(\Magento\Store\Api\Data\StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMockBuilder(\Psr\Log\LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->request = $this->getMockBuilder(\Magento\Framework\App\Request\Http::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeWebsiteRelation = $this->getMockBuilder(\Doofinder\Feed\Model\StoreWebsiteRelation::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configCollectionFactory = $this->getMockBuilder(
            \Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory::class
        )->disableOriginalConstructor()
        ->getMock();

        $this->configCollection = $this->getMockBuilder(
            \Magento\Config\Model\ResourceModel\Config\Data\Collection::class
        )->disableOriginalConstructor()
        ->getMock();

        $this->configValue = $this->getMockBuilder(\Magento\Framework\App\Config\Value::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper = $this->objectManager->getObject(
            \Doofinder\Feed\Helper\StoreConfig::class,
            [
                'scopeConfig'           => $this->scopeConfig,
                'storeManager'          => $this->storeManager,
                'logger'                => $this->logger,
                'storeWebsiteRelation'  => $this->storeWebsiteRelation,
                '_request'              => $this->request,
                'configCollection'      => $this->configCollectionFactory,
            ]
        );
    }

    /**
     * Test getStoreConfig() method
     *
     * @return void
     */
    public function testGetStoreConfig()
    {
        $this->storeManager->expects($this->once())
            ->method('getStore')
            ->willReturn($this->storeInterface);

        $this->storeInterface->expects($this->once())
            ->method('getCode')
            ->willReturn('default');

        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(\Doofinder\Feed\Helper\StoreConfig::FEED_ATTRIBUTES_CONFIG)
            ->willReturn(['attr1' => 'value1', 'attr2' => 'value2']);

        $this->scopeConfig->expects($this->at(1))
            ->method('getValue')
            ->with(\Doofinder\Feed\Helper\StoreConfig::FEED_CRON_CONFIG)
            ->willReturn(['enabled' => 1, 'start_time' => '10,30,0']);

        $this->scopeConfig->expects($this->at(2))
            ->method('getValue')
            ->with(\Doofinder\Feed\Helper\StoreConfig::FEED_SETTINGS_CONFIG)
            ->willReturn(['split_configurable_products' => 0, 'image_size' => 'small', 'export_product_prices' => 1]);

        $expected = [
            'store_code'                    => 'default',
            'enabled'                       => 1,
            'split_configurable_products'   => 0,
            'export_product_prices'         => 1,
            'image_size'                    => 'small',
            'start_time'                    => ['10', '30', '0'],
            'attributes'                    => ['attr1' => 'value1', 'attr2' => 'value2'],
        ];

        $result = $this->helper->getStoreConfig();

        $this->assertEquals($expected, $result);
    }

    /**
     * Test getStoreCode() method
     *
     * @return void
     */
    public function testGetStoreCode()
    {
        $this->storeManager->expects($this->once())
            ->method('getStore')
            ->willReturn($this->storeInterface);

        $this->storeInterface->expects($this->once())
            ->method('getCode')
            ->willReturn('default');

        $expected = 'default';

        $this->assertSame($expected, $this->helper->getStoreCode());
    }

    /**
     * Test getStoreCodes by store id
     *
     * @return void
     */
    public function testGetStoreCodesByStoreId()
    {
        $storeId = 1;
        $storeCode = 'store_code';
        $this->request->method('getParam')->with('store')->willReturn($storeId);

        $store = $this->getMockBuilder(\Magento\Store\Model\Store::class)
            ->disableOriginalConstructor()
            ->getMock();
        $store->method('getCode')->willReturn($storeCode);

        $this->storeManager->method('getStore')->with($storeId)->willReturn($store);
        $this->assertEquals([$storeCode], $this->helper->getStoreCodes());
    }

    /**
     * Test getStoreCodes by website id
     *
     * @return void
     */
    public function testGetStoreCodesByWebsiteId()
    {
        $expected = ['store_one', 'store_two'];

        $store = $this->getMockBuilder(\Magento\Store\Model\Store::class)
            ->disableOriginalConstructor()
            ->getMock();

        $store->method('getCode')
            ->willReturnOnConsecutiveCalls('store_one', 'store_two');

        $store->method('isActive')
            ->willReturnOnConsecutiveCalls(true, true);

        $this->request->expects($this->at(1))->method('getParam')->with('website')->willReturn(1);
        $this->storeWebsiteRelation->method('getStoreByWebsiteId')->with(1)->willReturn([1, 2]);

        $this->storeManager->expects($this->exactly(2))
            ->method('getStore')
            ->withConsecutive([1], [2])
            ->willReturnOnConsecutiveCalls($store, $store);

        $this->assertEquals($expected, $this->helper->getStoreCodes());
    }

    /**
     * Test getStoreCodes without specified website or store
     * @param boolean $onlyActive
     * @param array $expected
     * @return void
     * @dataProvider providerTestGetStoreCodes
     */
    public function testGetStoreCodes($onlyActive, array $expected)
    {
        $store = $this->getMockBuilder(\Magento\Store\Model\Store::class)
            ->disableOriginalConstructor()
            ->getMock();
        $store
            ->method('getCode')
            ->willReturnOnConsecutiveCalls('active', 'inactive');
        $store
            ->method('isActive')
            ->willReturnOnConsecutiveCalls(true, false);
        $stores = [$store, $store];

        $this->request->method('getParam')->willReturn(null);
        $this->storeManager->method('getStores')->willReturn($stores);

        $this->assertEquals($expected, $this->helper->getStoreCodes($onlyActive));
    }

    /**
     * Data provider for testGetStoreCodes()
     *
     * @return array
     */
    public function providerTestGetStoreCodes()
    {
        return [
            [true, ['active']],
            [false, ['active', 'inactive']]
        ];
    }

    /**
     * Test getApiKey() method
     *
     * @return void
     */
    public function testGetApiKey()
    {
        $expected = 'sample_api_key';

        $this->scopeConfig
            ->expects($this->once())
            ->method('getValue')
            ->with('doofinder_config_config/doofinder_account/api_key', 'default', null)
            ->willReturn($expected);

        $this->assertSame($expected, $this->helper->getApiKey());
    }

    /**
     * Test isStoreSearchEngineEnabled() method
     *
     * @return void
     */
    public function testIsStoreSearchEngineEnabled()
    {
        $storeCode = 'sample';
        $expected = true;

        $this->scopeConfig
            ->expects($this->once())
            ->method('getValue')
            ->with('doofinder_config_config/doofinder_search_engine/enabled', 'store', $storeCode)
            ->willReturn($expected);

        $this->assertSame($expected, $this->helper->isStoreSearchEngineEnabled($storeCode));
    }

    /**
     * Test isStoreSearchEngineEnabledNoCached() method
     *
     * @return void
     */
    public function testIsStoreSearchEngineEnabledNoCached()
    {
        $storeId = '2';
        $value = '1';

        $this->configCollectionFactory->method('create')->willReturn($this->configCollection);

        $this->configCollection
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([
                $this->configValue,
                $this->configValue,
            ]));

        $this->configCollection
            ->method('addScopeFilter')
            ->with('stores', $storeId, \Doofinder\Feed\Helper\StoreConfig::SEARCH_ENGINE_CONFIG)
            ->willReturnSelf();

        $getDataWith = [['path'], ['path'], ['value']];

        $this->configValue
            ->expects($this->exactly(count($getDataWith)))
            ->method('getData')
            ->withConsecutive(...$getDataWith)
            ->willReturnOnConsecutiveCalls(
                \Doofinder\Feed\Helper\StoreConfig::SEARCH_ENGINE_CONFIG . '/hash_id', // unexpected path
                \Doofinder\Feed\Helper\StoreConfig::SEARCH_ENGINE_CONFIG . '/enabled', // expected path
                $value
            );

        $this->assertSame((bool) $value, $this->helper->isStoreSearchEngineEnabledNoCached($storeId));
    }

    /**
     * Test getHashId() method
     *
     * @return void
     */
    public function testGetHashId()
    {
        $storeCode = 'sample';
        $expected = 'sample_hash_id';

        $this->scopeConfig
            ->expects($this->once())
            ->method('getValue')
            ->with('doofinder_config_config/doofinder_search_engine/hash_id', 'store', $storeCode)
            ->willReturn($expected);

        $this->assertSame($expected, $this->helper->getHashId($storeCode));
    }

    /**
     * Test isInternalSearchEnabled() method.
     *
     * @param  boolean $enabled
     * @param  boolean $expected
     * @return void
     * @dataProvider providerTestIsInternalSearchEnabled
     */
    public function testIsInternalSearchEnabled($enabled, $expected)
    {
        $storeCode = 'sample';

        $this->scopeConfig->method('getValue')->with('catalog/search/engine', 'store', $storeCode)
            ->willReturn($enabled);

        $this->assertEquals($expected, $this->helper->isInternalSearchEnabled($storeCode));
    }

    /**
     * Data provider for testIsInternalSearchEnabled() test
     *
     * @return array
     */
    public function providerTestIsInternalSearchEnabled()
    {
        return [
            [true, true],
            [false, false],
        ];
    }

    /**
     * Test isAtomicUpdatesEnabled() method.
     *
     * @param  string $engine
     * @param  boolean $atomic
     * @param  boolean $expected
     * @return void
     * @dataProvider providerTestIsAtomicUpdatesEnabled
     */
    public function testIsAtomicUpdatesEnabled($engine, $atomic, $expected)
    {
        $storeCode = 'sample';

        $this->scopeConfig->method('getValue')->will($this->returnValueMap([
            ['catalog/search/engine', 'store', $storeCode, $engine],
            ['doofinder_config_index/feed_settings/atomic_updates_enabled', 'store', $storeCode, $atomic],
        ]));

        $this->assertEquals($expected, $this->helper->isAtomicUpdatesEnabled($storeCode));
    }

    /**
     * Data provider for testIsAtomicUpdatesEnabled() test
     *
     * @return array
     */
    public function providerTestIsAtomicUpdatesEnabled()
    {
        return [
            [true, true, false],
            [false, false, false],
            [true, false, false],
            [false, true, true],
        ];
    }

    /**
     * Test isExportCategoriesInNavigation() method.
     *
     * @param  boolean $value
     * @param  boolean $expected
     * @return void
     * @dataProvider providerTestIsExportCategoriesInNavigation
     */
    public function testIsExportCategoriesInNavigation($value, $expected)
    {
        $storeCode = 'sample';

        $this->scopeConfig->method('getValue')->will($this->returnValueMap([
            ['doofinder_config_index/feed_settings/categories_in_navigation', 'store', $storeCode, $value],
        ]));

        $this->assertEquals($expected, $this->helper->isExportCategoriesInNavigation($storeCode));
    }

    /**
     * Data provider for testIsExportCategoriesInNavigation() test
     *
     * @return array
     */
    public function providerTestIsExportCategoriesInNavigation()
    {
        return [
            [true,  true],
            [false, false],
        ];
    }

    /**
     * Test getSearchLayerScript() method
     *
     * @return void
     */
    public function testGetSearchLayerScript()
    {
        $storeCode = 'sample';
        $script = '<script type="text/javascript">sample script</script>';

        $this->scopeConfig->method('getValue')->will($this->returnValueMap([
            ['doofinder_config_config/doofinder_layer/script', 'store', $storeCode, $script],
        ]));

        $this->assertEquals($script, $this->helper->getSearchLayerScript($storeCode));
    }
}
