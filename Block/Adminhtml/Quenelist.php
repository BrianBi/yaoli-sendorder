<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/19
 * Time: 11:36
 */
namespace Yaoli\Sendorder\Block\Adminhtml;

class Quenelist extends \Magento\Backend\Block\Widget\Grid\Container
{
    protected function _construct()
    {
        $this->_controller = 'adminhtml_quenelist';
        $this->_blockGroup = 'Yaoli_Sendorder';
        $this->_headerText = __('Quenelists');
        parent::_construct();
    }
}