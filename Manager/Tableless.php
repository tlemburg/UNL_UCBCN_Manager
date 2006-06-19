<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP version 4.0                                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Alexey Borzov <borz_off@cs.msu.su>                          |
// |          Adam Daniel <adaniel1@eesus.jnj.com>                        |
// |          Bertrand Mansion <bmansion@mamasam.com>                     |
// |          Mark Wiesemann <wiesemann@php.net>                          |
// +----------------------------------------------------------------------+
//
// $Id $

require_once 'HTML/QuickForm/Renderer/Default.php';

/**
 * A renderer for HTML_QuickForm that only uses XHTML and CSS but no
 * table tags
 * 
 * You need to specify a stylesheet like the one that you find in
 * data/stylesheet.css to make this work.
 * 
 * @access public
 */
class HTML_QuickForm_Renderer_Tableless extends HTML_QuickForm_Renderer_Default
{
   /**
    * Header Template string
    * @var      string
    * @access   private
    */
    var $_headerTemplate = 
        "\n\t\t<legend>{header}</legend>";

   /**
    * Element template string
    * @var      string
    * @access   private
    */
    var $_elementTemplate = 
        "\n\t\t<label class=\"qflabel\"><!-- BEGIN required --><span style=\"color: #ff0000\">*</span><!-- END required -->{label}</label><div class=\"qfelement<!-- BEGIN error --> error<!-- END error -->\"><!-- BEGIN error --><span class=\"error\">{error}</span><br /><!-- END error -->{element}</div><br />";

   /**
    * Form template string
    * @var      string
    * @access   private
    */
    var $_formTemplate = 
        "\n<form{attributes}>\n\t{hidden}\n{content}\n</form>";

   /**
    * Template used when opening a fieldset
    * @var      string
    * @access   private
    */
    var $_openFieldsetTemplate = "\n\t<fieldset>";

   /**
    * Template used when closing a fieldset
    * @var      string
    * @access   private
    */
    var $_closeFieldsetTemplate = "\n\t</fieldset>";

   /**
    * Required Note template string
    * @var      string
    * @access   private
    */
    var $_requiredNoteTemplate = 
        "\n\t\t{requiredNote}";

    function HTML_QuickForm_Renderer_Tableless()
    {
        $this->HTML_QuickForm_Renderer_Default();
    } // end constructor

   /**
    * Called when visiting a header element
    *
    * @param    object     An HTML_QuickForm_header element being visited
    * @access   public
    * @return   void
    */
    function renderHeader(&$header)
    {
        $name = $header->getName();
        if (!empty($name) && isset($this->_templates[$name])) {
            $header_html = str_replace('{header}', $header->toHtml(), $this->_templates[$name]);
        } else {
            $header_html = str_replace('{header}', $header->toHtml(), $this->_headerTemplate);
        }
        if ($this->_fieldsetIsOpen) {
            $this->_html .= $this->_closeFieldsetTemplate;
        }
        $this->_html .= $this->_openFieldsetTemplate . $header_html;
        $this->_fieldsetIsOpen = true;
    } // end func renderHeader

   /**
    * Helper method for renderElement
    *
    * @param    string      Element name
    * @param    mixed       Element label (if using an array of labels, you should set the appropriate template)
    * @param    bool        Whether an element is required
    * @param    string      Error message associated with the element
    * @access   private
    * @see      renderElement()
    * @return   string      Html for element
    */
    function _prepareTemplate($name, $label, $required, $error)
    {
        if (is_array($label)) {
            $nameLabel = array_shift($label);
        } else {
            $nameLabel = $label;
        }
        if (isset($this->_templates[$name])) {
            $html = str_replace('{label}', $nameLabel, $this->_templates[$name]);
        } else {
            $html = str_replace('{label}', $nameLabel, $this->_elementTemplate);
        }
        if ($required) {
            $html = str_replace('<!-- BEGIN required -->', '', $html);
            $html = str_replace('<!-- END required -->', '', $html);
        } else {
            $html = preg_replace("/([ \t\n\r]*)?<!-- BEGIN required -->(\s|\S)*<!-- END required -->([ \t\n\r]*)?/i", '', $html);
        }
        if (isset($error)) {
            $html = str_replace('{error}', $error, $html);
            $html = str_replace('<!-- BEGIN error -->', '', $html);
            $html = str_replace('<!-- END error -->', '', $html);
        } else {
            // the following line contains the only change compared to the default
            // renderer:
            // we need to match ungreedy (U) here to allow two error blocks
            $html = preg_replace("/([ \t\n\r]*)?<!-- BEGIN error -->(\s|\S)*<!-- END error -->([ \t\n\r]*)?/iU", '', $html);
        }
        if (is_array($label)) {
            foreach($label as $key => $text) {
                $key  = is_int($key)? $key + 2: $key;
                $html = str_replace("{label_{$key}}", $text, $html);
                $html = str_replace("<!-- BEGIN label_{$key} -->", '', $html);
                $html = str_replace("<!-- END label_{$key} -->", '', $html);
            }
        }
        if (strpos($html, '{label_')) {
            $html = preg_replace('/\s*<!-- BEGIN label_(\S+) -->.*<!-- END label_\1 -->\s*/i', '', $html);
        }
        return $html;
    } // end func _prepareTemplate

   /**
    * Renders an element Html
    * Called when visiting an element
    *
    * @param object     An HTML_QuickForm_element object being visited
    * @param bool       Whether an element is required
    * @param string     An error message associated with an element
    * @access public
    * @return void
    */
    function renderElement(&$element, $required, $error)
    {
        if (!$this->_inGroup) {
            $html = $this->_prepareTemplate($element->getName(), $element->getLabel(), $required, $error);
            // the following lines (until the "elseif") were changed / added
            // compared to the default renderer
            $element_html = $element->toHtml();
            if (!is_null($element->getAttribute('id'))) {
                $id = $element->getAttribute('id');
            } else {
                $id = $element->getName();
            }
            $html = str_replace('<label', '<label for="' . $id . '"', $html);
            $element_html = str_replace('name="' . $id . '"',
                                        'id="' . $id . '" name="' . $id . '"',
                                        $element_html);
            $this->_html .= str_replace('{element}', $element_html, $html);

        } elseif (!empty($this->_groupElementTemplate)) {
            $html = str_replace('{label}', $element->getLabel(), $this->_groupElementTemplate);
            if ($required) {
                $html = str_replace('<!-- BEGIN required -->', '', $html);
                $html = str_replace('<!-- END required -->', '', $html);
            } else {
                $html = preg_replace("/([ \t\n\r]*)?<!-- BEGIN required -->(\s|\S)*<!-- END required -->([ \t\n\r]*)?/i", '', $html);
            }
            $this->_groupElements[] = str_replace('{element}', $element->toHtml(), $html);

        } else {
            $this->_groupElements[] = $element->toHtml();
        }
    } // end func renderElement

   /**
    * Called when visiting a form, before processing any form elements
    *
    * @param    object      An HTML_QuickForm object being visited
    * @access   public
    * @return   void
    */
    function startForm(&$form)
    {
        $this->_html = '';
        $this->_hiddenHtml = '';

        // the following code is new compared to the default renderer
        $firstVisibleElementIsAHeader = false;
        $lastElementWasHidden = true;
        foreach ($form->_elements as $element) {
            if (is_a($element, 'HTML_QuickForm_Group')) {
                $this->setGroupTemplate('<div class="qfgroup">{content}</div>', $element->getName());
            }
            if ($element->_type != 'hidden' && $lastElementWasHidden == true) {
                $lastElementWasHidden = false;
                if ($element->_type == 'header') {
                    $firstVisibleElementIsAHeader = true;
                }
            }
        }
        $this->_fieldsetIsOpen = false;
        if ($firstVisibleElementIsAHeader == false) {
            $this->_html .= $this->_openFieldsetTemplate;
            $this->_fieldsetIsOpen = true;
        }
    } // end func startForm

   /**
    * Called when visiting a form, after processing all form elements
    * Adds required note, form attributes, validation javascript and form content.
    * 
    * @param    object      An HTML_QuickForm object being visited
    * @access   public
    * @return   void
    */
    function finishForm(&$form)
    {
        // add a required note, if one is needed
        if (!empty($form->_required) && !$form->_freezeAll) {
            $this->_html .= str_replace('{requiredNote}', $form->getRequiredNote(), $this->_requiredNoteTemplate);
        }
        if ($this->_fieldsetIsOpen) {
            $this->_html .= $this->_closeFieldsetTemplate;
        }
        // add form attributes and content
        $html = str_replace('{attributes}', $form->getAttributes(true), $this->_formTemplate);
        if (strpos($this->_formTemplate, '{hidden}')) {
            $html = str_replace('{hidden}', $this->_hiddenHtml, $html);
        } else {
            $this->_html .= $this->_hiddenHtml;
        }
        $this->_hiddenHtml = '';
        $this->_html = str_replace('{content}', $this->_html, $html);
        // add a validation script
        if ('' != ($script = $form->getValidationScript())) {
            $this->_html = $script . "\n" . $this->_html;
        }
    } // end func finishForm

    /**
     * Sets the template used when opening a fieldset
     *
     * @param       string      The HTML used when opening a fieldset
     * @access      public
     * @return      void
     */
    function setOpenFieldsetTemplate($html)
    {
        $this->_openFieldsetTemplate = $html;
    } // end func setOpenFieldsetTemplate

    /**
     * Sets the template used when closing a fieldset
     *
     * @param       string      The HTML used when closing a fieldset
     * @access      public
     * @return      void
     */
    function setCloseFieldsetTemplate($html)
    {
        $this->_closeFieldsetTemplate = $html;
    } // end func setCloseFieldsetTemplate

} // end class HTML_QuickForm_Renderer_Default
?>
