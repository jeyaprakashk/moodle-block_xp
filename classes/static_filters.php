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
 * Block XP.
 *
 * @package    block_xp
 * @copyright  2014 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * block_xp_static_filters class
 *
 * @package    block_xp
 * @copyright  2017 Ruben Cancho
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_xp_static_filters {

    /**
     * Append static filters to current courses.
     *
     * @return boolean true if operation succeeded
     */
    public static function add_static_filters_to_courses() {
        global $DB;

        $records = $DB->get_records('block_xp_config');
        return self::execute_as_transaction(function() use ($records) {
            foreach($records as $record) {
                self::save_filters(self::get_static_filters(), $record->courseid, true);
            }
        });

    }

    /**
     *
     * Append default filters to a course, by default not adding them if already has filters
     *
     * @param int $courseid
     * @paran boolean ·$force force to add filters even if course already has filters.
     * @return boolean true if operation succeeded
     */
    public static function add_static_filters_to_course($courseid, $force = false) {
        return self::execute_as_transaction(function() use ($courseid, $force) {
            self::save_filters(self::get_static_filters(), $courseid, $force);
        });
    }

    protected static function execute_as_transaction($function) {
        global $DB;

        try {
            try {
                $transaction = $DB->start_delegated_transaction ();
                $function ();
                $transaction->allow_commit ();
            } catch ( Exception $e ) {
                debugging("Transaction exception, doing rollback:" . $e->getMessage());
                if (! empty ( $transaction ) && ! $transaction->is_disposed ()) {
                    $transaction->rollback ( $e );
                }
                return false;
            }
        } catch ( Exception $e ) {
            debugging("Rollback Exception:" . $e->getMessage());
            return false;
        }

        return true;

    }

    /**
     * Saves rules to course if no course rules exists.
     *
     * @param hash $rules
     * @param int $courseid
     * @param string $force force to save rules even if course rules exists.
     */
    protected static function save_filters($rules, $courseid, $force_save = false) {
        // check if we should add the rules
        if (!$force_save && self::course_has_filters($courseid)) return false;

        $sortorder = self::last_sortorder($courseid) + 1;

        foreach($rules as $rule) {
            self::save_filter($rule, $sortorder, $courseid);
            $sortorder += 1;
        }

        return true;
    }

    protected static function last_sortorder($courseid) {
        global $DB;

        $sortorder = $DB->get_field_select("block_xp_filters", "MAX(sortorder)",
                "courseid = " . $courseid);

        return is_null($sortorder) ? 0 : $sortorder;
    }

    /**
     * Check if the course has filters
     *
     * @param int $courseid
     * @return boolean
     */
    protected static function course_has_filters($courseid) {
        global $DB;

        return ($DB->count_records("block_xp_filters", ['courseid' => $courseid]) > 0);
    }

    /**
     * Save a filter to a course. A filter is a rule + sort order.
     *
     * @param hash $rule
     * @param int $sortorder
     * @param int $courseid
     */
    protected static function save_filter($rule, $sortorder, $courseid) {
        global $DB;

        $filter = $rule;
        $filter['courseid'] = $courseid;
        $filter['sortorder'] = $sortorder;
        $DB->insert_record("block_xp_filters", $filter);
    }

    protected static function get_static_filters() {
        $ruledata1 = [
                "_class" => "block_xp_ruleset",
                "method" => "any",
                "rules"  => [
                        [
                                "_class"   => "block_xp_rule_event",
                                "compare"  => "eq",
                                "value"    => "\\mod_book\\event\\course_module_viewed",
                                "property" => "eventname"
                        ],
                        [
                                "_class"   => "block_xp_rule_event",
                                "compare"  => "eq",
                                "value"    => "\\mod_forum\\event\\discussion_subscription_created",
                                "property" => "eventname"
                        ],
                        [
                                "_class"   => "block_xp_rule_event",
                                "compare"  => "eq",
                                "value"    => "\\mod_forum\\event\\subscription_created",
                                "property" => "eventname"
                        ],
                        [
                                "_class"   => "block_xp_rule_property",
                                "compare"  => "contains",
                                "value"    => "assessable_submitted",
                                "property" => "eventname"
                        ],
                        [
                                "_class"   => "block_xp_rule_property",
                                "compare"  => "contains",
                                "value"    => "assessable_uploaded",
                                "property" => "eventname"
                        ]]
        ];

        $filter1 = [
                "ruledata" => json_encode($ruledata1),
                "points"   => 0
        ];

        $ruledata2 = [
                "_class"   => "block_xp_rule_property",
                "compare"  => "eq",
                "value"    => "c",
                "property" =>"crud"
        ];

        $filter2 = [
                "ruledata" => json_encode($ruledata2),
                "points"   => 45
        ];

        $ruledata3 = [
                "_class"   => "block_xp_rule_property",
                "compare"  => "eq",
                "value"    => "r",
                "property" => "crud"
        ];

        $filter3 = [
                "ruledata" => json_encode($ruledata3),
                "points"   => 9
        ];

        $ruledata4 = [
                "_class"   => "block_xp_rule_property",
                "compare"  => "eq",
                "value"    => "u",
                "property" => "crud"
        ];

        $filter4 = [
                "ruledata" => json_encode($ruledata4),
                "points"   => 3
        ];

        $ruledata5 = [
                "_class"   => "block_xp_rule_property",
                "compare"  => "eq",
                "value"    => "d",
                "property" => "crud"
        ];

        $filter5 = [
                "ruledata" => json_encode($ruledata5),
                "points"   => 0
        ];

        return [$filter1, $filter2, $filter3, $filter4, $filter5];
    }
}