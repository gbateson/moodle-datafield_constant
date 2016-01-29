<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Class to represent a "datafield_constant" field
 *
 * this field acts as an extra API layer to restrict view and
 * edit access to any other type of field in a database activity
 *
 * @package    data
 * @subpackage datafield_constant
 * @copyright  2015 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.3
 */

class data_field_constant extends data_field_base {

    var $type = 'constant';

    function display_add_field($recordid = 0, $formdata = NULL) {
        return '';
    }

    function display_search_field($value = '') {
        return '';
    }

    function parse_search_field() {
        return '';
    }

    function generate_sql($tablealias, $value) {
        return array('', array());
    }

    function update_content($recordid, $value, $name='') {
        return false;
    }

    function display_browse_field($recordid, $template) {
        return $this->field->param1;
    }

    ///////////////////////////////////////////
    // custom methods
    ///////////////////////////////////////////

    /*
     * format a label in mod.html
     */
    public function format_table_row($name, $label, $text) {
        $label = $this->format_edit_label($name, $label);
        $output = $this->format_table_cell($label, 'c0').
                  $this->format_table_cell($text,  'c1');
        $output = html_writer::tag('tr', $output, array('class' => $name, 'style' => 'vertical-align: top;'));
        return $output;
    }

    /*
     * format a cell in mod.html
     */
    public function format_table_cell($text, $class) {
        return html_writer::tag('td', $text, array('class' => $class));
    }

    /*
     * format a label in mod.html
     */
    public function format_edit_label($name, $label) {
        return html_writer::tag('label', $label, array('for' => 'id_'.$name));
    }

    /*
     * format a text field in mod.html
     */
    public function format_edit_textfield($name, $value, $class, $size=10) {
        $params = array('type'  => 'text',
                        'id'    => 'id_'.$name,
                        'name'  => $name,
                        'value' => $value,
                        'class' => $class,
                        'size'  => $size);
        return html_writer::empty_tag('input', $params);
    }

    /*
     * format a textarea field in mod.html
     */
    public function format_edit_textarea($name, $value, $class, $rows=3, $cols=40) {
        $params = array('id'    => 'id_'.$name,
                        'name'  => $name,
                        'class' => $class,
                        'rows'  => $rows,
                        'cols'  => $cols);
        return html_writer::tag('textarea', $value, $params);
    }
}
