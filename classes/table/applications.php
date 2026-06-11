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

use context;
use context_course;
use core_table\dynamic as dynamic_table;
use core_table\local\filter\filterset;
use html_writer;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->dirroot . '/user/lib.php');

/**
 * Dynamic table for enrolment applications (MooTube embedded view).
 *
 * @package    enrol_gapply
 * @copyright  2026 Sokunthearith Makara <sokunthearithmakara@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class applications extends \table_sql implements dynamic_table {
    /** @var int Enrol instance id. */
    protected int $instanceid = 0;

    /** @var int Course id. */
    protected int $courseid = 0;

    /** @var context_course */
    protected context_course $context;

    /** @var string Application status filter. */
    protected string $status = 'new';

    /**
     * Constructor.
     *
     * @param string $uniqueid Table unique id.
     */
    public function __construct(string $uniqueid) {
        parent::__construct($uniqueid);
        $this->collapsible(false);
        $this->responsive = false;
        $this->use_pages = true;
        $this->pageable(true);
        $this->sort_default_column = 'timecreated';
        $this->sort_default_order = SORT_DESC;
        $this->set_attribute('class', 'generaltable table table-hover align-middle mb-0 mtube-gapply-applications-table w-100');
        $this->set_attribute('id', 'gapply-applications-table');
    }

    /**
     * Render the table.
     *
     * @param int $pagesize Page size.
     * @param bool $useinitialsbar Whether to use initials bar.
     * @param string $downloadhelpbutton Download help button HTML.
     */
    public function out($pagesize, $useinitialsbar, $downloadhelpbutton = '') {
        global $OUTPUT;

        $columns = ['select', 'id', 'applicant', 'timecreated'];
        $headers = [];

        $mastercheckbox = new \core\output\checkbox_toggleall('gapply-applications-table', true, [
            'id' => 'select-all-gapply-applications',
            'name' => 'select-all-gapply-applications',
            'label' => get_string('selectall'),
            'labelclasses' => 'visually-hidden',
            'classes' => 'form-check-input gapplyapplicationcheckbox m-1',
            'checked' => false,
        ]);
        $headers[] = $OUTPUT->render($mastercheckbox);
        $headers[] = get_string('id', 'enrol_gapply');
        $headers[] = get_string('userdetails', 'enrol_gapply');
        $headers[] = get_string('appliedon', 'enrol_gapply');

        if ($this->shows_timemodified_column()) {
            $columns[] = 'timemodified';
            $headers[] = get_string('modifiedon', 'enrol_gapply');
        }

        $this->define_columns($columns);
        $this->define_headers($headers);
        $this->define_header_column('applicant');

        $this->sortable(true, 'timecreated', SORT_DESC);
        if ($this->shows_timemodified_column()) {
            $this->sortable(true, 'timemodified', SORT_DESC);
        }
        $this->no_sorting('select');

        parent::out($pagesize, false, $downloadhelpbutton);
    }

    /**
     * Whether the last-modified column is shown (non-new status tabs).
     *
     * @return bool
     */
    protected function shows_timemodified_column(): bool {
        return $this->status !== 'new';
    }

    /**
     * Render table chrome without the top paging bar (bottom paging only).
     */
    public function start_html() {
        echo $this->get_dynamic_table_html_start();
        echo $this->render_reset_button();
        $this->print_initials_bar();

        if (in_array(TABLE_P_TOP, $this->showdownloadbuttonsat)) {
            echo $this->download_buttons();
        }

        $this->wrap_html_start();

        if ($this->responsive) {
            echo html_writer::start_tag('div', ['class' => 'table-responsive']);
        }
        echo html_writer::start_tag('table', $this->attributes) . $this->render_caption();
    }

    /**
     * Checkbox column.
     *
     * @param \stdClass $row Row data.
     * @return string
     */
    public function col_select(\stdClass $row): string {
        global $OUTPUT;

        $checkbox = new \core\output\checkbox_toggleall('gapply-applications-table', false, [
            'classes' => 'form-check-input gapplyapplicationcheckbox m-1',
            'id' => 'application' . $row->id,
            'name' => 'application' . $row->id,
            'value' => $row->id,
            'checked' => false,
            'label' => get_string('selectitem', 'moodle', fullname($row)),
            'labelclasses' => 'accesshide',
        ]);

        return $OUTPUT->render($checkbox);
    }

    /**
     * Application id column.
     *
     * @param \stdClass $row Row data.
     * @return string
     */
    public function col_id(\stdClass $row): string {
        return html_writer::link(
            '#',
            $row->id,
            [
                'class' => 'gapply-view-application font-weight-bold',
                'data-action' => 'view-application',
                'data-id' => $row->id,
            ]
        );
    }

    /**
     * Applicant column.
     *
     * @param \stdClass $row Row data.
     * @return string
     */
    public function col_applicant(\stdClass $row): string {
        global $OUTPUT;

        $picture = $OUTPUT->user_picture($row, ['size' => 35, 'class' => 'mr-2', 'link' => false]);
        $profileurl = new moodle_url('/user/view.php', [
            'id' => $row->userid,
        ]);
        $name = html_writer::link(
            $profileurl,
            fullname($row),
            ['class' => 'font-weight-bold']
        );

        return $picture . $name;
    }

    /**
     * Applied date column.
     *
     * @param \stdClass $row Row data.
     * @return string
     */
    public function col_timecreated(\stdClass $row): string {
        return userdate($row->timecreated, get_string('strftimedatetimeshort', 'langconfig'));
    }

    /**
     * Last modified date column.
     *
     * @param \stdClass $row Row data.
     * @return string
     */
    public function col_timemodified(\stdClass $row): string {
        if (empty($row->timemodified)) {
            return '';
        }

        return userdate($row->timemodified, get_string('strftimedatetimeshort', 'langconfig'));
    }

    /**
     * Query the database.
     *
     * @param int $pagesize Page size.
     * @param bool $useinitialsbar Whether to use initials bar.
     */
    public function query_db($pagesize, $useinitialsbar = true) {
        global $DB;

        $fullname = $DB->sql_fullname('u.firstname', 'u.lastname');
        $fields = 'g.id, g.userid, g.timecreated, g.timemodified, u.firstname, u.lastname, u.firstnamephonetic,
            u.lastnamephonetic, u.middlename, u.alternatename, u.picture, u.imagealt, u.email, ' . $fullname . ' AS applicant';
        $from = '{enrol_gapply} g JOIN {user} u ON u.id = g.userid';

        $where = 'g.instance = :instanceid AND g.status = :status AND u.deleted = 0';
        $params = [
            'instanceid' => $this->instanceid,
            'status' => $this->status,
        ];

        $keywordsfilter = $this->filterset->has_filter('keywords')
            ? $this->filterset->get_filter('keywords')
            : null;
        if ($keywordsfilter && $keywordsfilter->get_filter_values()) {
            $keyword = trim($keywordsfilter->get_filter_values()[0]);
            if ($keyword !== '') {
                $like = '%' . $DB->sql_like_escape($keyword) . '%';
                $fullname = $DB->sql_fullname('u.firstname', 'u.lastname');
                $where .= ' AND (' . $DB->sql_like($DB->sql_cast_to_char('g.id'), ':keywordid', false) .
                    ' OR ' . $DB->sql_like($fullname, ':keywordname', false) . ')';
                $params['keywordid'] = $like;
                $params['keywordname'] = $like;
            }
        }

        $this->set_sql($fields, $from, $where, $params);
        parent::query_db($pagesize, false);
    }

    /**
     * Apply filterset.
     *
     * @param filterset $filterset Filterset.
     */
    public function set_filterset(filterset $filterset): void {
        global $DB;

        $this->instanceid = (int) $filterset->get_filter('instanceid')->current();
        $this->status = (string) $filterset->get_filter('status')->current();

        $instance = $DB->get_record('enrol', ['id' => $this->instanceid, 'enrol' => 'gapply'], '*', MUST_EXIST);
        $this->courseid = (int) $instance->courseid;
        $this->context = context_course::instance($this->courseid, MUST_EXIST);

        if ($this->shows_timemodified_column()) {
            $this->sort_default_column = 'timemodified';
            $this->sort_default_order = SORT_DESC;
        } else {
            $this->sort_default_column = 'timecreated';
            $this->sort_default_order = SORT_DESC;
        }

        parent::set_filterset($filterset);
    }

    /**
     * Guess base URL.
     */
    public function guess_base_url(): void {
        $this->baseurl = new moodle_url('/course/view.php', ['id' => $this->courseid]);
    }

    /**
     * Get context.
     *
     * @return context
     */
    public function get_context(): context {
        return $this->context;
    }

    /**
     * Capability check.
     *
     * @return bool
     */
    public function has_capability(): bool {
        return has_capability('enrol/gapply:manage', $this->context);
    }

    /**
     * Render empty state inside the table (headers visible, no alert banner).
     */
    public function print_nothing_to_display() {
        $this->start_html();
        $this->print_headers();

        echo html_writer::start_tag('tbody');
        $colcount = count($this->columns);
        $message = html_writer::div(
            get_string('noapplications', 'enrol_gapply'),
            'mtube-gapply-empty-message'
        );
        echo html_writer::tag(
            'tr',
            html_writer::tag('td', $message, [
                'colspan' => $colcount,
                'class' => 'mtube-gapply-empty-cell',
            ]),
            ['class' => 'mtube-gapply-empty-row']
        );
        echo html_writer::end_tag('tbody');
        echo html_writer::end_tag('table');
        $this->wrap_html_finish();
        echo $this->get_dynamic_table_html_end();
    }

    /**
     * Finish table output without padding blank rows to the page size.
     */
    public function finish_html() {
        global $OUTPUT;

        if (!$this->started_output) {
            $this->print_nothing_to_display();
            return;
        }

        echo html_writer::end_tag('tbody');
        echo html_writer::end_tag('table');
        $this->wrap_html_finish();

        if ($this->use_pages) {
            $pagingbar = new \paging_bar($this->totalrows, $this->currpage, $this->pagesize, $this->baseurl);
            $pagingbar->pagevar = $this->request[TABLE_VAR_PAGE];
            echo $OUTPUT->render($pagingbar);
        }

        echo $this->get_dynamic_table_html_end();
    }
}
