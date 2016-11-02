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

// prevent direct access to this script
defined('MOODLE_INTERNAL') || die();

// get required files
require_once($CFG->dirroot.'/mod/data/field/admin/field.class.php');

class data_field_constant extends data_field_base {

    var $type = 'constant';

    /**
     * displays the settings for this action field on the "Fields" page
     *
     * @return void, but output is echo'd to browser
     */
    function display_edit_field() {
        data_field_admin::check_lang_strings($this);
        parent::display_edit_field();
    }

    function display_add_field($recordid = 0, $formdata = NULL) {
        if (empty($this->field->param2)) {
            return '';
        }
        return data_field_admin::format_hidden_field('field_'.$this->field->id, 0);
    }

    /**
     * add a new admin field from the "Fields" page
     */
    function insert_field() {
        $this->update_constants();
        parent::insert_field();
    }

    /**
     * update settings for this admin field sent from the "Fields" page
     *
     * @return void, but output is echo'd to browser
     */
    function update_field() {
        $this->update_constants();
        parent::update_field();
    }

    function update_content($recordid, $value, $name='') {
        global $DB;

        if (empty($this->field->param2)) {
            return false; // auto-increment not required
        }

        $select = 'recordid = ? AND fieldid = ? AND content IS NOT NULL AND content != ? AND content != ?';
        if ($DB->record_exists_select('data_content', $select, array($recordid, $this->field->id, '', '0'))) {
            return false; // auto-increment value is already set in DB
        }

        $value = $this->get_autoincrement($recordid);
        return parent::update_content($recordid, $value, $name);
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

    function display_browse_field($recordid, $template) {
        if (empty($this->field->param2)) {
            return $this->field->param1; // same value for EVERY record
        } else {
            // auto increment field (value is different for each record)
            return parent::display_browse_field($recordid, $template);
        }
    }

    /**
     * text export returns the constant value from $field->param1
     */
    function export_text_value($record) {
        return $this->field->param1;
    }

    ///////////////////////////////////////////
    // custom methods
    ///////////////////////////////////////////

    /**
     * update constant values in all records
     */
    public function update_constants() {
        global $DB;

        $constant      = optional_param('param1', '', PARAM_RAW);
        $autoincrement = optional_param('param2', 0,  PARAM_INT);
        $format        = optional_param('param3', '', PARAM_TEXT);

        if ($autoincrement==0 || $constant=='') {
            // we don't want to delete previous constant values
            // because they may be important e.g. receipt numbers
            return false;
        }

        // sql to select records whose autoincrement constant is not yet set
        $sql = 'SELECT dr.id, dc.id AS contentid, dc.fieldid, dc.content '.
               'FROM {data_records} dr LEFT JOIN {data_content} dc ON dr.id = dc.recordid AND dc.fieldid = ? '.
               'WHERE dr.dataid = ? AND (dc.content IS NULL OR dc.content = ? OR dc.content = ?)'.
               'ORDER BY dr.timecreated';
        $params = array($this->field->id, $this->data->id, '', '0');
        if (! $records = $DB->get_records_sql($sql, $params)) {
            return false; // no records with missing constant
        }

        foreach ($records as $record) {
            $content = (object)array(
                'recordid' => $record->id,
                'fieldid'  => $this->field->id,
                'content'  => $this->get_autoincrement($record->id)
            );
            if (empty($record->contentid)) {
                $DB->insert_record('data_content', $content);
            } else {
                $content->id = $record->contentid;
                $DB->update_record('data_content', $content);
            }
        }

        return true; // one or more records were updated
    }

    /**
     * get autoincrement value for a given $recordid
     *
     * this method expects that you have already checked
     * to see that no previous value exists for this constant
     */
    public function get_autoincrement($recordid) {
        global $DB;

        // sql to get highest increment value so far
        // we use the length of the content string
        // to mimic "natural sorting" of the data
        $sql = $DB->sql_length('content');
        $sql = "SELECT id, $sql AS contentlength, content ".
               'FROM {data_content} '.
               'WHERE fieldid = ? '.
               'ORDER BY contentlength DESC, content DESC';
        if ($value = $DB->get_records_sql($sql, array($this->field->id), 0, 1)) {
            $value = reset($value);
            $value = $value->content;
            if (empty($value)) {
                $value = $this->field->param1;
            }
            $increment = 1;
        } else {
            $value = $this->field->param1;
            $increment = 0;
        }

        if (! preg_match('/[0-9]+/', $value, $matches)) {
            return ''; // non-numeric value cannot be incremented
        }

        $value = intval($matches[0]) + $increment;

        if ($fmt = $this->field->param3) {
            $value = sprintf($fmt, $value);
        }

        return $value;
    }
}
