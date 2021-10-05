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

    /**#@+
     * Database codes to indicate type of this constant field.
     * These values are stored in the ""param2" field of the
     * "data_fields" table in the Moodle database.
     *
     * @var integer
     */
    const TYPE_CONSTANT      = 0;
    const TYPE_AUTOINCREMENT = 1;
    const TYPE_RANDOM        = 2;
    const TYPE_FILEJS        = 3;
    const TYPE_FILECSS       = 4;
    /**#@-*/

    /**#@+
     * value affecting the length of the random strings.
     *
     * @var integer
     */
    const MIN_RANDOM_LENGTH     = 2;
    const DEFAULT_RANDOM_LENGTH = 4;
    const MAX_RANDOM_LENGTH     = 8;
    /**#@-*/

    /**
     * generate HTML to display icon for this field type on the "Fields" page
     */
    function image() {
        return data_field_admin::field_icon($this);
    }

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

        $value = $this->get_constant_value($this->field->param2);
        return data_field_admin::update_content_multilang($this->field->id, $recordid, $value, $name);
    }

    /**
     * delete content associated with a constant field
     * when the field is deleted from the "Fields" page
     */
    function delete_content($recordid=0) {
        data_field_admin::delete_content_files($this);
        return parent::delete_content($recordid);
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
     * export_text_value
     *
     */
    public function export_text_value($record) {
    	return data_field_admin::get_export_value($record->fieldid);
    }

    /**
     * Return the plugin configs for external functions.
     *
     * @return array the list of config parameters
     * @since Moodle 3.3
     */
    public function get_config_for_external() {
    	return data_field_admin::get_field_params($this->field);
    }

    ///////////////////////////////////////////
    // custom methods
    ///////////////////////////////////////////

    /**
     * update constant values in all records
     */
    public function update_constants() {
        global $DB;

        $value  = optional_param('param1', '', PARAM_RAW);
        $type   = optional_param('param2', 0,  PARAM_INT);
        $format = optional_param('param3', '', PARAM_TEXT);

        // don't do anything about empty constant values
        if ($type==self::TYPE_CONSTANT && $value=='') {
            return false;
        }

        // sql to select ALL records using this field
        $select = 'dr.id, dc.id AS contentid, dc.fieldid, dc.content';
        $from   = '{data_records} dr LEFT JOIN {data_content} dc ON dr.id = dc.recordid AND dc.fieldid = ?';
        $where  = 'dr.dataid = ?';
        $order  = 'dr.timecreated';
        $params = array($this->field->id, $this->data->id);

        if ($type==self::TYPE_CONSTANT) {
            $where .= ' AND (dc.content IS NULL OR '.$DB->sql_compare_text('dc.content', 255).' != ?)';
            $params[] = "$value"; // make sure it is a string
        } else {
            // select only records whose constant value is not yet set
            $where .= ' AND (dc.content IS NULL OR dc.content = ? OR dc.content = ?)';
            $params[] = '';
            $params[] = '0';
        }

        $records = "SELECT $select FROM $from WHERE $where ORDER BY $order";
        if (! $records = $DB->get_records_sql($records, $params)) {
            return false; // no records to update
        }

        foreach ($records as $record) {
            $content = (object)array(
                'recordid' => $record->id,
                'fieldid'  => $this->field->id,
                'content'  => $this->get_constant_value($type)
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
     * get constant value for a record that needs to be updated
     *
     * this method expects that you have already checked
     * to see that no previous value exists for this constant
     */
    public function get_constant_value($type) {
        global $CFG, $DB, $PAGE;

        if ($type==self::TYPE_CONSTANT) {
            return $this->field->param1; // a constant value
        }

        if ($type==self::TYPE_AUTOINCREMENT) {
            // sql to get highest field value so far
            // we use the length of the value string
            // to mimic "natural sorting" of the data
            $select = 'id, '.$DB->sql_length('content').' AS contentlength, content';
            $from   = '{data_content}';
            $where  = 'fieldid = ? AND content IS NOT NULL';
            $order  = 'contentlength DESC, content DESC';
            $params = array($this->field->id);

            // try to select only numeric strings (MySQL and PostgreSQL)
            if ($sql_regex_supported = $DB->sql_regex_supported()) {
                $where .= ' AND content '.$DB->sql_regex().' ?';
                $params[] = '^[0-9]+$';
                $limitnum = 1; // fetch only highest numeric record
            } else {
                $where .= ' AND content != ?';
                $params[] = '';
                $limitnum  = 0; // fetch ALL records
            }

            // extract highest $value of this field in the database
            $value = null;
            $values = "SELECT $select FROM $from WHERE $where ORDER BY $order";
            if ($values = $DB->get_records_sql($values, $params, 0, $limitnum)) {
                if ($sql_regex_supported) {
                    $value = reset($values);
                } else {
                    // get first numeric value (MSSQL and Oracle)
                    while ($value = array_shift($values)) {
                        if (preg_match('/^[0-9]+$/', $value->content)) {
                            break; // we found a numeric value - YAY!
                        }
                    }
                }
            }

            // increment the $value
            if (empty($value)) {
                $value = 0;
            } else {
                $value = intval($value->content);
            }
            $value = max($value + 1, $this->field->param1);

            // format the $value
            if ($fmt = $this->field->param3) {
                $value = sprintf($fmt, $value);
            }

            return $value;
        }

        if ($type==self::TYPE_RANDOM) {
            if (is_numeric($this->field->param1)) {
                $length = $this->field->param1;
                $length = max($length, self::MIN_RANDOM_LENGTH);
                $length = min($length, self::MAX_RANDOM_LENGTH);
            } else {
                $length = self::DEFAULT_RANDOM_LENGTH;
            }
            $count = 0;
            $select = 'fieldid = ? AND '.$DB->sql_compare_text('content', 255).' = ?';
            while (true) {
                $value = substr(uniqid(), -$length).'-'.substr(md5(mt_rand()), 0, $length);
                $params = array($this->field->id, $value);
                if (! $DB->record_exists_select('data_content', $select, $params)) {
                    return $value;
                }
                $count++;
                if ($count > 10000) {
                    break; // infinite loop ?!
                }
            }
        }

        if ($type==self::TYPE_FILEJS || $type==self::TYPE_FILECSS) {
            if ($url = $this->field->param1) {

                if ($CFG->slasharguments) {
                    $file_php = 'file.php';
                } else {
                    $file_php = 'file.php?file=';
                }
                $pluginfile_php = 'plugin'.$file_php;
                $replace = array(
                    '%wwwroot%' => $CFG->wwwroot,
                    '%sitefiles%' => $CFG->wwwroot.'/'.$file_php.'/'.SITEID,
                    '%coursefiles%' => $CFG->wwwroot.'/'.$file_php.'/'.$this->data->course,
                    '%modulefiles%' => $CFG->wwwroot.'/'.$pluginfile_php.'/'.$this->context->id.'/mod_data/intro'
                );
                $url = strtr($url, $replace_pairs);
                if (! preg_match('/^https?:\/\//', $url)) {
                    $url = new moodle_url($url);
                }

                if ($type==self::TYPE_URL) {
                    return $url;
                }

                if ($PAGE->requires->is_head_done()) {
                    // hmm, the document head has alreay been printed
                    // this is not allowed really :-(
                    if ($type==self::TYPE_FILEJS) {
                        $params = array('type' => 'tetxt/javascript', 'src' => $url);
                        return html_writer::tag('script', $params);
                    }
                    if ($type==self::TYPE_FILECSS) {
                        $params = array('rel' => 'stylesheet', 'type' => 'tetxt/css', 'href' => $url);
                        return html_writer::tag('link', $params);
                    }
                } else {
                    if ($type==self::TYPE_FILEJS) {
                        $PAGE->requires->js($url);
                        return true;
                    }
                    if ($type==self::TYPE_FILECSS) {
                        $PAGE->requires->css($url);
                        return true;
                    }
                }
            }
        }

        // Oops - there was a problem !!
        return null;
    }

    /**
     * get list of action times
     */
    static public function get_constant_types() {
        $plugin = 'datafield_constant';
        return array(self::TYPE_CONSTANT      => get_string('constant',      $plugin),
                     self::TYPE_AUTOINCREMENT => get_string('autoincrement', $plugin),
                     self::TYPE_RANDOM        => get_string('random',        $plugin),
                     self::TYPE_FILEJS        => get_string('filejs',        $plugin),
                     self::TYPE_FILECSS       => get_string('filecss',       $plugin));
    }
}
