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

namespace orcaltisrv_gradebookservices\task;

/**
 * Tests cleaning up the gradebook services task.
 *
 * @package orcaltisrv_gradebookservices
 * @category test
 * @copyright 2018 Mark Nelson <markn@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cleanup_test extends \advanced_testcase {

    /**
     * Test set up.
     *
     * This is executed before running any test in this file.
     */
    public function setUp(): void {
        $this->resetAfterTest();
    }

    /**
     * Test the cleanup task.
     */
    public function test_cleanup_task() {
        global $DB;

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create a few ORCALTI items.
        $orcalti = $this->getDataGenerator()->create_module('orcalti', ['course' => $course->id]);
        $orcalti2 = $this->getDataGenerator()->create_module('orcalti', ['course' => $course->id]);

        $conditions = [
            'courseid' => $course->id,
            'itemtype' => 'mod',
            'itemmodule' => 'orcalti',
            'iteminstance' => $orcalti->id
        ];

        // Get the grade items.
        $gradeitem = $DB->get_record('grade_items', $conditions);

        $conditions['iteminstance'] = $orcalti2->id;
        $gradeitem2 = $DB->get_record('grade_items', $conditions);

        // Insert these into the 'orcaltisrv_gradebookservices' table.
        $data = new \stdClass();
        $data->gradeitemid = $gradeitem->id;
        $data->courseid = $course->id;
        $DB->insert_record('orcaltisrv_gradebookservices', $data);

        $data->gradeitemid = $gradeitem2->id;
        $DB->insert_record('orcaltisrv_gradebookservices', $data);

        $task = new cleanup_task();
        $task->execute();

        // Check they both still exist.
        $this->assertEquals(2, $DB->count_records('orcaltisrv_gradebookservices'));

        // Delete the first ORCALTI activity.
        course_delete_module($orcalti->cmid);

        // Run the task again.
        $task = new cleanup_task();
        $task->execute();

        // Check only the second grade item exists.
        $gradebookserviceitems = $DB->get_records('orcaltisrv_gradebookservices');
        $this->assertCount(1, $gradebookserviceitems);

        $gradebookserviceitem = reset($gradebookserviceitems);

        $this->assertEquals($gradeitem2->id, $gradebookserviceitem->gradeitemid);
    }

    /**
     * Test the cleanup task with a manual grade item.
     */
    public function test_cleanup_task_with_manual_item() {
        global $CFG, $DB;

        // This is required when running the unit test in isolation.
        require_once($CFG->libdir . '/gradelib.php');

        // Create a manual grade item for a course.
        $course = $this->getDataGenerator()->create_course();
        $params = [
            'courseid' => $course->id,
            'itemtype' => 'manual'
        ];
        $gradeitem = new \grade_item($params);
        $gradeitem->insert();

        // Insert it into the 'orcaltisrv_gradebookservices' table.
        $data = new \stdClass();
        $data->gradeitemid = $gradeitem->id;
        $data->courseid = $course->id;
        $DB->insert_record('orcaltisrv_gradebookservices', $data);

        // Run the task.
        $task = new cleanup_task();
        $task->execute();

        // Check it still exist.
        $this->assertEquals(1, $DB->count_records('orcaltisrv_gradebookservices'));

        // Delete the manual item.
        $gradeitem->delete();

        // Run the task again.
        $task = new cleanup_task();
        $task->execute();

        // Check it has been removed.
        $this->assertEquals(0, $DB->count_records('orcaltisrv_gradebookservices'));
    }
}
