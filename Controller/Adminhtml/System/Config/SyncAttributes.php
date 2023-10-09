<?php

namespace Usercom\Analytics\Controller\Adminhtml\System\Config;

class SyncAttributes extends \Magento\Backend\App\Action
{
    protected \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory;
    protected array $syncTimeArray;
    protected \Usercom\Analytics\Helper\Usercom $userComHelper;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Usercom\Analytics\Block\System\Config\SyncTime $syncTime,
        \Usercom\Analytics\Helper\Usercom $userComHelper
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->syncTimeArray     = $syncTime->toOptionArray();
        $this->userComHelper     = $userComHelper;
        parent::__construct($context);
    }

    public function execute()
    {
//        $key     = $_POST["time"] ?? null;
//        $lastDay = $_POST["lastDat"] ?? null;

        $data = $this->userComHelper->listAttributes();
        $message = 'Success';

        return ( ! empty($errorMessage)) ? $this->result($errorMessage, [], 409) : $this->result($message, $data, 200);
    }

    public function result($message, $data, $code)
    {
        $result = $this->resultJsonFactory->create();
        $result->setHttpResponseCode($code);

        return $result->setData(['status' => $message, 'data' => $data]);
    }
}
