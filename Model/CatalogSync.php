<?php

namespace Usercom\Analytics\Model;

class CatalogSync extends ProductSyncAbstract
{
    public function productView(string $message): void
    {
        $this->productEventType = $this->helper::PRODUCT_EVENT_VIEW;
        $this->singleProductEvent($message);
    }
}
