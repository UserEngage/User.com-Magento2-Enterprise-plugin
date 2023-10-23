<?php

namespace Usercom\Analytics\Model;

class CatalogSync extends ProductSyncAbstract
{
    public function productView(string $message): void
    {
        $this->eventType = $this->helper::PRODUCT_EVENT_VIEW;
        $this->singleProductEvent($message);
    }
}
