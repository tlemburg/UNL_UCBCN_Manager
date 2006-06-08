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
 * You need to specify a stylesheet like the following in your page:
 * <style type="text/css">
 *  form {
 *    margin: 0;
 *    padding: 0;
 *  }
 *  
 *  form br {
 *    clear: left;
 *  }
 *  
 *  form label { 
 *    display: block;
 *    float: left;
 *    width: 150px;
 *    padding: 0;
 *    margin: 0 0 5px;
 *    text-align: right;
 *  }
 *  
 *  form input, form textarea {
 *    width: auto;
 *    margin:0 0 5px 10px;
 *  }
 *  
 *  form span.error {
 *    color: red;
 *    margin: 5px 0 0 10px;
 *  }
 *  
 *  form div.error {
 *    border: 1px solid red;
 *    margin-bottom: 10px;
 *  }
 *  
 *  form div.header {
 *    white-space: nowrap;
 *    background-color: #CCCCCC;
 *    margin-bottom: 10px;
 *    padding: 2px;
 *  }
 *  
 *  textarea {
 *    overflow: auto;
 *  }
 * </style>
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
        "\n\t<div class=\"header\"><b>{header}</b></div>";

   /**
    * Element template string
    * @var      string
    * @access   private
    */
    var $_elementTemplate = 
        "\n\t<div<!-- BEGIN error --> class=\"error\"<!-- END error -->><!-- BEGIN error -->\n\t\t<label></label><span class=\"error\">{error}</span><br /><!-- END error -->\n\t\t<label><!-- BEGIN required --><span style=\"color: #ff0000\">*</span><!-- END required -->{label}</label>{element}<br />\n</div>";

   /**
    * Form template string
    * @var      string
    * @access   private
    */
    var $_formTemplate = 
        "\n<form{attributes}>\n{hidden}\n{content}\n\n</form>";

   /**
    * Required Note template string
    * @var      string
    * @access   private
    */
    var $_requiredNoteTemplate = 
        "\n{requiredNote}";

    function HTML_QuickForm_Renderer_Tableless()
    {
        $this->HTML_QuickForm_Renderer_Default();
    } // end constructor

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
            // the following line contains the only change against default renderer:
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

} // end class HTML_QuickForm_Renderer_Default
?>