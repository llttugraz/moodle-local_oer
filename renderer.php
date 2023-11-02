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
 * Open Educational Resources Plugin
 *
 * @package    local_oer
 * @author     Christian Ortner <christian.ortner@tugraz.at>
 * @copyright  2017 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Class local_oer_renderer
 */
class local_oer_renderer extends plugin_renderer_base {
    /**
     * Select users to add to allowance/dissallowance list.
     *
     * TODO: mention the part of moodle where this is taken from.
     *
     * @param object $options options
     * @return string
     * @throws coding_exception
     */
    public function oer_user_selector(&$options) {
        $formcontent = html_writer::empty_tag('input',
                ['name' => 'sesskey', 'value' => sesskey(), 'type' => 'hidden']);

        $table = new html_table();
        $table->size = ['45%', '10%', '45%'];
        $table->attributes['class'] = 'roleassigntable generaltable generalbox boxaligncenter';
        $table->summary = '';
        $table->cellspacing = 0;
        $table->cellpadding = 0;

        // LTR/RTL support, for drawing button arrows in the right direction.
        if (right_to_left()) {
            $addarrow = '▶';
            $removearrow = '◀';
        } else {
            $addarrow = '◀';
            $removearrow = '▶';
        }

        // Create the add and remove button.
        $addinput = html_writer::empty_tag('input',
                [
                        'name' => 'add', 'id' => 'add', 'type' => 'submit',
                        'value' => $addarrow . ' ' . get_string('add'),
                        'title' => get_string('add'),
                ]);
        $addbutton = html_writer::tag('div', $addinput, ['id' => 'addcontrols']);
        $removeinput = html_writer::empty_tag('input',
                [
                        'name' => 'remove', 'id' => 'remove', 'type' => 'submit',
                        'value' => $removearrow . ' ' . get_string('remove'),
                        'title' => get_string('remove'),
                ]);
        $removebutton = html_writer::tag('div', $removeinput, ['id' => 'removecontrols']);

        // Create the three cells.
        $label = html_writer::tag('label', get_string('oerusers', 'local_oer'),
                ['for' => 'removeselect']);
        $label = html_writer::tag('p', $label);
        $authoriseduserscell = new html_table_cell($label .
                $options->alloweduserselector->display(true));
        $authoriseduserscell->id = 'existingcell';
        $buttonscell = new html_table_cell($addbutton . html_writer::empty_tag('br') . $removebutton);
        $buttonscell->id = 'buttonscell';
        $label = html_writer::tag('label', get_string('potusers', 'local_oer'),
                ['for' => 'addselect']);
        $label = html_writer::tag('p', $label);
        $otheruserscell = new html_table_cell($label .
                $options->potentialuserselector->display(true));
        $otheruserscell->id = 'potentialcell';

        $cells = [$authoriseduserscell, $buttonscell, $otheruserscell];
        $row = new html_table_row($cells);
        $table->data[] = $row;
        $formcontent .= html_writer::table($table);

        $formcontent = html_writer::tag('div', $formcontent);

        $actionurl = new moodle_url('/local/oer/views/manage.php');
        $html = html_writer::tag('form', $formcontent,
                ['id' => 'assignform', 'action' => $actionurl, 'method' => 'post']);
        return $html;
    }
}
