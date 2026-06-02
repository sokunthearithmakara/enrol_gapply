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

namespace enrol_gapply\table;

use core_table\local\filter\filterset;
use core_table\local\filter\integer_filter;
use core_table\local\filter\string_filter;

/**
 * Filterset for the gapply applications dynamic table (MooTube embedded view).
 *
 * @package    enrol_gapply
 * @copyright  2026 Sokunthearith Makara <sokunthearithmakara@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class applications_filterset extends filterset {
    /**
     * Export filters as an indexed array for the dynamic table webservice.
     *
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize(): array {
        $filters = [];
        foreach ($this->get_filters() as $filter) {
            $filters[] = $filter->jsonSerialize();
        }

        return [
            'jointype' => $this->get_join_type(),
            'filters' => $filters,
        ];
    }

    /**
     * Required filters.
     *
     * @return array
     */
    public function get_required_filters(): array {
        return [
            'instanceid' => integer_filter::class,
            'status' => string_filter::class,
        ];
    }

    /**
     * Optional filters.
     *
     * @return array
     */
    public function get_optional_filters(): array {
        return [
            'keywords' => string_filter::class,
        ];
    }
}
