<?php

namespace Usercom\Analytics\Plugin;

use Magento\Customer\Model\Session;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Http\Context;

class CustomerDataContext
{
    /**
     * @var Session
     */
    private Session $customerSession;

    /**
     * @var Context
     */
    private Context $httpContext;

    /**
     * Constructor
     *
     * @param Session $customerSession
     * @param Context $httpContext
     */
    public function __construct(
        Session $customerSession,
        Context $httpContext
    ) {
        $this->customerSession = $customerSession;
        $this->httpContext     = $httpContext;
    }

    /**
     * @param ActionInterface $subject
     */
    public function beforeExecute(ActionInterface $subject)
    {
        if ($this->customerSession->getCustomer() !== null) {
            $this->httpContext->setValue('usercom_user_id', $this->customerSession->getCustomer()->getData('usercom_user_id'), false);
        }
    }
}
