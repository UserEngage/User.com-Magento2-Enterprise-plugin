<?php

namespace Usercom\Analytics\Model;

class ProductSyncAbstract
{
    protected \Usercom\Analytics\Helper\Usercom $helper;
    protected \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository;
    protected \Usercom\Analytics\Helper\Data $dataHelper;
    protected \Magento\Catalog\Api\ProductRepositoryInterface $productRepository;

    protected string $eventType;

    public function __construct(
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Usercom\Analytics\Helper\Usercom $helper,
        \Usercom\Analytics\Helper\Data $dataHelper,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->customerRepository = $customerRepository;
        $this->productRepository  = $productRepository;
        $this->helper             = $helper;
        $this->dataHelper         = $dataHelper;
        $this->logger             = $logger;
    }

    /**
     * @param string $message
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function productEvent(string $message): void
    {
        if ( ! $this->dataHelper->isModuleEnabled()) {
            return;
        }
        list($productId, $usercomUserId, $usercomKey) = $this->extractParams($message);

        $this->logger->info(
            "CheckoutEvent: " . $this->eventType,
            [
                'productId:'      => $productId,
                'usercom_user_id' => $usercomUserId ?? null,
                'usercom_key'     => $usercomKey ?? null,
                'moduleEnabled'   => $this->dataHelper->isModuleEnabled(),

            ]
        );

        if ($productId !== null) {
            list($productEventData, $usercomProductId) = $this->prepareProduct($productId);

            $data         = $this->getEventData($productId, $productEventData, $usercomKey);
            $eventReponse = $this->helper->createProductEvent($usercomProductId, $data);
            $this->logger->info("CheckoutEventResponse: " . $this->eventType, [json_encode($eventReponse)]);
        }
    }

    /**
     * @param string $message
     *
     * @return array
     */
    protected function extractParams(string $message): array
    {
        $messageData   = json_decode($message, true);
        $productId     = $messageData['productId'];
        $usercomUserId = $messageData['usercom_user_id'];
        $usercomKey    = $messageData['user_key'];

        return [$productId, $usercomUserId, $usercomKey];
    }

    /**
     * @param int $productId
     *
     * @return array
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\StateException
     */
    protected function prepareProduct(int $productId): array
    {
        $product = $this->productRepository->getById($productId);
        $this->logger->info("PrepareProduct", ['productId:' => $productId]);
        $productEventData = $this->mapProductData($product);
        $this->logger->info("MappedProductData", ['productId:' => $productId, 'productEventData' => $productEventData]);
        $productData = $product->getData();

        if (isset($productData['extension_attributes']) &&
            ( ! property_exists($productData['extension_attributes'], 'usercom_product_id') ||
              empty($productData['extension_attributes']->usercom_product_id))
        ) {
            $this->logger->info("extension_attributes usercom_product_id is empty");
            $usercomProductId = $this->helper->getUsercomProductId($this->helper::PRODUCT_PREFIX . $product->getId());
            $this->logger->info("CatalogSync UCPID:", ['usercomProductId' => $usercomProductId]);
            if ($usercomProductId === null) {
                $usercomProduct   = $this->helper->createProduct($productEventData);
                $usercomProductId = $usercomProduct->id ?? null;
                $product->setCustomAttribute('usercomProductId', $usercomProductId);
                $this->productRepository->save($product);
            }
            $this->logger->info("CatalogSync UCPID:", ["usercomProduct" => $usercomProductId]);
        } else {
            $usercomProductId = $productData['extension_attributes']->usercom_product_id;
            $this->logger->info("CatalogSync usercomProductId:", ['usercomProductId' => $usercomProductId]);
        }

        return [$productEventData, $usercomProductId];
    }

    protected function mapProductData(\Magento\Catalog\Api\Data\ProductInterface $product): array
    {
        $media = $product->getMediaGalleryEntries();

        $fileUrl = ( ! empty($media[0])) ? $media[0]->getFile() : null;
        $data    = [
            "custom_id"   => $this->helper::PRODUCT_PREFIX . $product->getId(),
            'name'        => $product->getName(),
            'price'       => (float)$product->getFinalPrice(),
            'sku'         => $product->getSku(),
            'product_url' => $product->getProductUrl(),
            'image_url'   => $fileUrl
        ];

        $this->logger->info("CatalogProduct:", $data);

        return $data;
    }

    /**
     * @param int $productId
     * @param array $productEventData
     * @param string|null $usercomKey
     *
     * @return array
     */
    protected function getEventData(int $productId, array $productEventData, string $usercomKey = null): array
    {
        //            if ( ! empty($usercomUserId)) {
//                $data["custom_id"] = $usercomUserId ?? null;
//            }
        $userObject = $this->helper->getUserByUserKey($usercomKey);

        return [
            "id"         => $this->helper::PRODUCT_PREFIX . $productId,
            "data"       => $productEventData,
            "event_type" => $this->eventType,
            "timestamp"  => time(),
            "user_key"   => $usercomKey ?? null,
            "user_id"    => $userObject->id ?? null,
        ];
    }

    /**
     * @param string $message
     *
     * @return void
     */
    protected function log(string $message)
    {
        $this->logger->info("CatalogSync", [$message]);
    }
}
