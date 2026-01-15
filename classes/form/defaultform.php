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

namespace enrol_gapply\form;

/**
 * Class defaultform
 *
 * @package    enrol_gapply
 * @copyright  2026 Sokunthearith Makara <sokunthearithmakara@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class defaultform extends \moodleform {
    /**
     * Add elements to form.
     * @return void
     */
    public function definition() {
        $mform = $this->_form;
        $output = $this->_customdata['output'];
        $instance = $this->_customdata['instance'];
        $plugin = enrol_get_plugin('gapply');
        $heading = $plugin->get_instance_name($instance);
        if ($instance->name != null) {
            $heading = $instance->name;
        }

        $mform->addElement('header', 'heading', format_text($heading, FORMAT_HTML));

        $mform->addElement('html', $output);
    }
}
