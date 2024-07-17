<?php
/**
 * Copyright © TAL All rights reserved.
 */

namespace TAL\StockIndex\Block\Adminhtml\System\Config\Fieldset;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Module\ModuleList\Loader;
use \Magento\Framework\Data\Form\Element\Renderer\RendererInterface;

class Hint extends \Magento\Backend\Block\Template implements RendererInterface
{
    /**
     * @var string
     */
    protected $_template = 'TAL_StockIndex::system/config/fieldset/hint.phtml';

    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    protected $_metaData;

    /**
     * @var \Magento\Framework\Module\ModuleList\Loader
     */
    protected $_loader;

    /**
     * @param Context $context
     * @param ProductMetadataInterface $productMetaData
     * @param Loader $loader
     * @param array $data
     */
    public function __construct(
        Context $context,
        ProductMetadataInterface $productMetaData,
        Loader $loader,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_metaData = $productMetaData;
        $this->_loader = $loader;
    }

    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return mixed
     */
    public function render(AbstractElement $element)
    {
        return $this->toHtml();
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        $modules = $this->_loader->load();
        $v = "";
        if (isset($modules['TAL_StockIndex'])) {
            $v = "v" . $modules['TAL_StockIndex']['setup_version'];
        }

        return $v;
    }

    /**
     * @return mixed
     */
    public function getModulePage()
    {
        return $this->_helper->getConfigModule('module_page_link');
    }
}