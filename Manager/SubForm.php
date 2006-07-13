<?php
/**
 * This file extends the subform class to override the toHTML issue.
 * 
 */

require_once('DB/DataObject/FormBuilder/QuickForm/SubForm.php');

class UNL_UCBCN_Manager_SubForm extends HTML_QuickForm_SubForm {

    /**
     * renders the element
     *
     * @return string the HTML for the element
     */
    function toHtml()
    {
        if (!isset($this->_renderer) || !is_a($this->_renderer, 'HTML_QuickForm_Renderer_Default')) {
            $this->_renderer = clone(HTML_QuickForm::defaultRenderer());
        }
        $this->_renderer->_html =
            $this->_renderer->_hiddenHtml =
            $this->_renderer->_groupTemplate = 
            $this->_renderer->_groupWrap = '';
        $this->_renderer->_groupElements = array();
        $this->_renderer->_inGroup = false;
        $this->_renderer->setFormTemplate(preg_replace('!</?form[^>]*>!', '', $this->_renderer->_formTemplate));
        $this->_subForm->accept($this->_renderer);
        if (isset($this->_renderer->_fieldsetIsOpen) && $this->_renderer->_fieldsetIsOpen) {
            $this->_renderer->_fieldsetIsOpen = false;
            $this->_subForm->accept($this->_renderer);
            $html = $this->_renderer->toHtml();
            $this->_renderer->_fieldsetIsOpen = true;
            return $html;
        } else {
            return $this->_renderer->toHtml();
        }
    }
}

if (class_exists('HTML_QuickForm')) {
    HTML_QuickForm::registerElementType('subForm', __FILE__, 'UNL_UCBCN_Manager_SubForm');
}

?>