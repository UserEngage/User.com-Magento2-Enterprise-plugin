<?php

namespace Usercom\Analytics\Block;

class Frontend extends \Magento\Framework\View\Element\Template
{
    protected $helper;
    protected $httpContext;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\Http\Context $httpContext,
        \Usercom\Analytics\Helper\Data $helper
    ) {
        $this->httpContext = $httpContext;
        $this->helper      = $helper;
        parent::__construct($context);
    }

    public function isModuleEnabled()
    {
        return $this->helper->isModuleEnabled();
    }

    public function getApi()
    {
        return $this->helper->getApi();
    }

    public function getSubdomain()
    {
        return $this->helper->getSubdomain();
    }

    public function getUsercomUserId()
    {
        $isLoggedIn = $this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH);
        return ($isLoggedIn) ? $this->httpContext->getValue('usercom_user_id') : "";
    }
}
