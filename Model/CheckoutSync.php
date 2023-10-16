<?php

namespace Usercom\Analytics\Model;

class CheckoutSync extends ProductSyncAbstract
{

    /**
     * @param string $message
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function productEvent(string $message): void
    {
        $this->eventType = $this->helper::PRODUCT_EVENT_ADD_TO_CART;
        list($productId, $usercomUserId, $usercomKey) = $this->extractParams($message);

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
            list($productEventData, $usercomProductId) = $this->prepareProduct($productId);

            $data         = $this->getEventData($productId, $productEventData, $usercomKey);
            $eventReponse = $this->helper->createProductEvent($usercomProductId, $data);
            $this->logger->info("CreateEventResponse:", [json_encode($eventReponse)]);
        }
        $this->logger->info("CatalogSync EOF", []);
    }


}
