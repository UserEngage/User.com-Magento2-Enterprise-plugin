<?php

namespace Usercom\Analytics\Model;

use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order;

class ProductSyncAbstract
{
    protected \Usercom\Analytics\Helper\Usercom $helper;
    protected \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository;
    protected \Usercom\Analytics\Helper\Data $dataHelper;
    protected \Magento\Catalog\Api\ProductRepositoryInterface $productRepository;
    protected \Magento\Quote\Api\CartRepositoryInterface $quoteRepository;
    protected \Magento\Sales\Api\OrderRepositoryInterface $orderRepository;

    protected string $eventType;

    public function __construct(
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Usercom\Analytics\Helper\Usercom $helper,
        \Usercom\Analytics\Helper\Data $dataHelper,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->customerRepository = $customerRepository;
        $this->productRepository  = $productRepository;
        $this->quoteRepository    = $quoteRepository;
        $this->orderRepository    = $orderRepository;
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
    protected function singleProductEvent(string $message): void
    {
        if ( ! $this->dataHelper->isModuleEnabled()) {
            return;
        }

        list($usercomUserId, $usercomKey, $messageData) = $this->extractParams($message);
        $productId = $messageData['productId'];

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

            $data          = $this->getProductEventData($productId, $productEventData, $usercomKey);
            $eventResponse = $this->helper->createProductEvent($usercomProductId, $data);
            $this->logger->info("CheckoutEventResponse: " . $this->eventType, [json_encode($eventResponse)]);
        }
    }

    /**
     * @param string $message
     *
     * @return array
     */
    protected function extractParams(string $message): array
    {
        $messageData = json_decode($message, true);

        $usercomUserId = $messageData['usercom_user_id'];
        $usercomKey    = $messageData['user_key'];

        return [$usercomUserId, $usercomKey, $messageData];
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
    protected function prepareProduct(int $productId, $qty = 1, $price = null): array
    {
        $product = $this->productRepository->getById($productId);
        $this->logger->info("PrepareProduct", ['productId:' => $productId]);
        $productEventData = $this->mapProductData($product, $qty, $price);
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

    protected function mapProductData(\Magento\Catalog\Api\Data\ProductInterface $product, $qty = 1, $price = null): array
    {
        $media        = $product->getMediaGalleryEntries();
        $brand        = '';
        $categoryName = '';
        $fileUrl      = ( ! empty($media[0])) ? $media[0]->getFile() : null;
        $data         = [
            "id"            => $this->helper::PRODUCT_PREFIX . $product->getId(),
            "custom_id"     => $this->helper::PRODUCT_PREFIX . $product->getId(),
            'name'          => $product->getName(),
            'price'         => $price ?? (float)$product->getFinalPrice(),
            'quantity'      => $qty,
            'brand'         => $brand,
            'category_name' => $categoryName,
            'sku'           => $product->getSku(),
            'product_url'   => $product->getProductUrl(),
            'image_url'     => $fileUrl
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
    protected function getProductEventData(int $productId, array $productEventData, string $usercomKey = null, $time = null): array
    {
        if ( ! empty($time)) {
            $time = strtotime($time);
        }
//            if ( ! empty($usercomUserId)) {
//                $data["custom_id"] = $usercomUserId ?? null;
//            }
        $userObject = $this->helper->getUserByUserKey($usercomKey);

        return [
            "id"         => $this->helper::PRODUCT_PREFIX . $productId,
            "data"       => $productEventData,
            "event_type" => $this->eventType,
            "timestamp"  => $time ?? time(),
            "user_key"   => $usercomKey ?? null,
            "user_id"    => $userObject->id ?? null,
        ];
    }

    /**
     * @param string $message
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function cartEvent(string $message): void
    {
        if ( ! $this->dataHelper->isModuleEnabled()) {
            return;
        }

        list($usercomUserId, $usercomKey, $messageData) = $this->extractParams($message);
        $quoteId = $messageData['quoteId'];
        /** @var Quote $quote */
        $quote  = $this->quoteRepository->get($quoteId);
        $items  = $quote->getItems();
        $totals = $quote->getTotals();
        $this->log("CartEvent: " . $this->eventType, json_encode($totals));
        $cartEventData = [
            'products'        => [],
//            'tax'             => $totals['tax']->getValue(),
            'revenue'         => $quote->getGrandTotal(),
            'currency'        => $quote->getGrandTotal(),
            'payment_method'  => $quote->getPayment()->getMethodInstance()->getTitle(),
            'coupon'          => $quote->getCouponCode(),
//            'order number'    => '',
            'shipping'        => $quote->getShippingAddress()->getShippingAmount(),
            'registered_user' => ! $quote->getCustomerIsGuest(),
        ];

        if ($quoteId !== null) {
            foreach ($items as $item) {
                /** @var CartItemInterface $item */
                list($productEventData, $usercomProductId) = $this->prepareProduct($item->getItemId(), $item->getQty(), $item->getPrice());
                $cartEventData['products'][] = $productEventData;
                $data                        = $this->getProductEventData($quoteId, $productEventData, $usercomKey, $quote->getUpdatedAt());
                $eventResponse               = $this->helper->createProductEvent($usercomProductId, $data);
            }
            $eventData = $this->getEventData($cartEventData, $usercomKey, $quote->getUpdatedAt());
            $this->helper->createEvent($eventData);
            $this->logger->info("CheckoutEventResponse: " . $this->eventType, [json_encode($eventResponse)]);
        }
    }

    /**
     * @param string $message
     *
     * @return void
     */
    protected function log(string $message, $data)
    {
        $this->logger->info($message, [$data]);
    }

    /**
     * @param array $data
     * @param string|null $usercomKey
     *
     * @return array
     */
    protected function getEventData(array $data, string $usercomKey = null, $time = null): array
    {
        if ( ! empty($time)) {
            $time = strtotime($time);
        }
//            if ( ! empty($usercomUserId)) {
//                $data["custom_id"] = $usercomUserId ?? null;
//            }
        $userObject = $this->helper->getUserByUserKey($usercomKey);

        return [
            "data"       => $data,
            "event_type" => $this->eventType,
            "timestamp"  => $time ?? time(),
            "user_key"   => $usercomKey ?? null,
            "user_id"    => $userObject->id ?? null,
        ];
    }

    /**
     * @param string $message
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function orderEvent(string $message): void
    {
        if ( ! $this->dataHelper->isModuleEnabled()) {
            return;
        }

        list($usercomUserId, $usercomKey, $messageData) = $this->extractParams($message);
        $orderId = $messageData['orderId'];
        /** @var Order $quote */
        $order         = $this->orderRepository->get($orderId);
        $items         = $order->getItems();
        $cartEventData = [
            'products'        => [],
            'tax'             => $order->getTaxAmount(),
            'revenue'         => $order->getGrandTotal(),
            'currency'        => $order->getGrandTotal(),
            'payment_method'  => $order->getPayment()->getMethodInstance()->getTitle(),
            'coupon'          => $order->getCouponCode(),
            'order number'    => $order->getIncrementId(),
            'shipping'        => $order->getShippingAddress()->getShippingAmount(),
            'registered_user' => ! $order->getCustomerIsGuest(),
        ];

        if ($orderId !== null) {
            foreach ($items as $item) {
                /** @var OrderItemInterface $item */
                list($productEventData, $usercomProductId) = $this->prepareProduct(
                    $item->getItemId(),
                    $item->getQtyOrdered(),
                    $item->getPriceInclTax()
                );
                $cartEventData['products'][] = $productEventData;
                $data                        = $this->getProductEventData($orderId, $productEventData, $usercomKey, $order->getCreatedAt());
                $eventResponse               = $this->helper->createProductEvent($usercomProductId, $data);
            }
            $eventData = $this->getEventData($cartEventData, $usercomKey, $order->getCreatedAt());
            $this->helper->createEvent($eventData);
            $this->logger->info("CheckoutEventResponse: " . $this->eventType, [json_encode($eventResponse)]);
        }
    }
}
