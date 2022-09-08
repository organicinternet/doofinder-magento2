<?php

namespace Doofinder\Feed\Console\Command;

use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\State;
use Magento\Framework\App\Area;
use Magento\Framework\Indexer\SaveHandler\Batch;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Doofinder\Feed\Model\ChangedProduct\DocumentsProvider;
use Psr\Log\LoggerInterface;
use Doofinder\Feed\Helper\Item;
use Doofinder\Feed\Helper\StoreConfig;
use Doofinder\Feed\Helper\Indice as IndiceHelper;
use Magento\CatalogSearch\Model\Indexer\Fulltext\Action\Full;
use Doofinder\Feed\Model\Indexer\Data\Mapper;

/**
 * Class PerformDelayedUpdates
 * This class reflects current product data in Doofinder on command run.
 */
class PerformFullReIndex extends Command
{
    /**
     * @var State
     */
    private $state;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var \Doofinder\Feed\Model\ChangedProduct\DocumentsProvider
     */
    private $documentsProvider;

    /**
     * @var Batch
     */
    private $batch;

    /**
     * @var Item
     */
    private $itemHelper;

    /** 
     * @var StoreConfig 
     */
    private $storeConfig;

    /**
     * @var Full
     */
    private $fullAction;

    /**
     * @var Mapper
     */
    private $mapper;

    /**
     * @var integer
     */
    private $batchSize;

    /**
     * PerformDelayedUpdates constructor.
     * @param State $state
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Doofinder\Feed\Model\ChangedProduct\DocumentsProvider $documentsProvider
     * @param \Magento\Framework\Indexer\SaveHandler\Batch $batch
     * @param LoggerInterface $logger
     * @param Item $itemHelper
     * @param StoreConfig $storeConfig
     * @param Full $fullAction
     * @param Mapper $mapper
     * @param integer $batchSize
     */
    public function __construct(
      State $state, 
      CollectionFactory $productCollectionFactory,
      DocumentsProvider $documentsProvider,
      Batch $batch,
      LoggerInterface $logger,
      Item $itemHelper,
      StoreConfig $storeConfig,
      Full $fullAction,
      Mapper $mapper,
      $batchSize = 100
    )
    {
        $this->state = $state;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->documentsProvider = $documentsProvider;
        $this->batch = $batch;
        $this->logger = $logger;
        $this->itemHelper = $itemHelper;
        $this->storeConfig  = $storeConfig;
        $this->fullAction = $fullAction;
        $this->mapper = $mapper;
        $this->batchSize = $batchSize;
        parent::__construct(null);
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this->setName('doofinder:indexer:reindex')
            ->setDescription('Execute Full ReIndex');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws LocalizedException If Delayed updates disabled.
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Started</info>');

        $self = $this;
        $this->state->emulateAreaCode(Area::AREA_FRONTEND, function () use ($self) {
          if ($this->storeConfig->isUpdateOnSave()) {
              try {
                  foreach ($this->storeConfig->getAllStores() as $store) {
                      $indice = IndiceHelper::MAGENTO_INDICE_NAME;
                      $this->updateAllProducts($store, $indice);
                  }
              } catch (\Exception $e) {
                  $this->logger->error('[Doofinder] Error processing updates: ' . $e->getMessage());
              }
          }
        });

        $output->writeln('<info>Finished</info>');
    }

    private function updateAllProducts($store, $indice) {
      $collection = $this->productCollectionFactory->create();
      if ($collection->getSize()) {
          $updated = $this->fullAction->rebuildStoreIndex($store->getId(), $collection->getAllIds());
          foreach ($this->batch->getItems($updated, $this->batchSize) as $batchDocuments) {
              $items = $this->mapper->get('update')->map($batchDocuments, (int)$store->getId());
              if (count($items)) {
                  try {
                      $this->logger->debug('[UpdateInBulk]');
                      $this->logger->debug(\Zend_Json::encode($items));
                      $this->itemHelper->updateItemsInBulk($items, $store, $indice);
                  } catch (\Exception $e) {
                      $this->logger->error(
                          sprintf(
                              '[Doofinder] There was an error while updating items in bulk: %s',
                              $e->getMessage()
                          )
                      );
                  }
              }
          }
      }
    }
}
