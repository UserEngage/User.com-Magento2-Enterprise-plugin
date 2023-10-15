<?php

namespace Usercom\Analytics\Model;

class CatalogSync
{
    private \Usercom\Analytics\Helper\Usercom $helper;
    private \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository;
    private \Usercom\Analytics\Helper\Data $dataHelper;
    private \Magento\Catalog\Api\ProductRepositoryInterface $productRepository;

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
     */
    public function log(string $message)
    {
        $this->logger->info("CatalogSync", [$message]);
    }

    /**
     * @param string $message
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function productEvent(string $message): void
    {
        $messageData   = json_decode($message, true);
        $productId     = $messageData['productId'];
        $usercomUserId = $messageData['usercom_user_id'];
        $usercomKey    = $messageData['user_key'];

        $this->logger->info(
            "CatalogProductEventStart",
            [
                'productId:'      => $productId,
                'usercom_user_id' => $usercomUserId ?? null,
                'usercom_key'     => $usercomKey ?? null,
                'moduleEnabled'   => $this->dataHelper->isModuleEnabled(),

            ]
        );
        if ($productId !== null) {
            if ( ! $this->dataHelper->isModuleEnabled()) {
                return;
            }
            $product = $this->productRepository->getById($productId);
            $this->logger->info(
                "CatalogSync",
                ['productId:' => $productId, 'usercom_user_id' => $usercomUserId ?? null]
            );
            $productEventData = $this->mapProductData($product);

            $this->logger->info(
                "CatalogSync",
                ['productId:' => $productId, 'usercom_user_id' => $usercomUserId ?? null, 'product' => $productEventData]
            );
            $productData = $product->getData();

            $this->logger->info("Catalog EventData:", ['productEventData' => $productEventData]);

            if (isset($productData['extension_attributes']) &&
                ( ! property_exists($productData['extension_attributes'], 'usercom_product_id') ||
                  empty($productData['extension_attributes']->usercom_product_id))
            ) {
                $this->logger->info("extension_attributes usercom_product_id is empty");
                $usercomProductId = $this->helper->getUsercomProductId('m2ee_' . $product->getId());
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

            $this->logger->info("CatalogSync SEND TO USER");

            $data = [
                "id"         => 'm2ee_' . $product->getId(),
                "data"       => $productEventData,
                "event_type" => "view",
                "timestamp"  => time()
            ];

            $data["user_key"] = $usercomKey ?? null;
            if ( ! empty($usercomUserId)) {
                $data["custom_id"] = $usercomUserId ?? null;
            }

            $userObject      = $this->helper->getUserByUserKey($usercomKey);
            $data["user_id"] = $userObject->id ?? null;
            $eventReponse    = $this->helper->createProductEvent($usercomProductId, $data);
            $this->logger->info("CreateEventResponse:", [json_encode($eventReponse)]);
        }
        $this->logger->info("CatalogSync EOF", []);
    }

    private function mapProductData(\Magento\Catalog\Api\Data\ProductInterface $product): array
    {
        $media = $product->getMediaGalleryEntries();

        $fileUrl = ( ! empty($media[0])) ? $media[0]->getFile() : null;
        $data    = [
            "custom_id"   => 'm2ee_' . $product->getId(),
            'name'        => $product->getName(),
            'price'       => (float)$product->getFinalPrice(),
            'sku'         => $product->getSku(),
            'product_url' => $product->getProductUrl(),
            'image_url'   => $fileUrl
        ];

        $this->logger->info("CatalogProduct:", $data);

        return $data;
    }
}
