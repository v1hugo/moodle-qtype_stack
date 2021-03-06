<?php
// This file is part of Stack - http://stack.bham.ac.uk/
//
// Stack is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Stack is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Stack.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This script lets the user send commands to the Maxima, and see the response.
 * This can be useful for learning about the CAS syntax, and also for testing
 * that maxima is working correctly.
 *
 * @copyright  2012 University of Birmingham
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../../config.php');

require_once(dirname(__FILE__) . '/locallib.php');
require_once(dirname(__FILE__) . '/stack/utils.class.php');
require_once(dirname(__FILE__) . '/stack/options.class.php');
require_once(dirname(__FILE__) . '/stack/cas/castext.class.php');
require_once(dirname(__FILE__) . '/stack/cas/casstring.class.php');
require_once(dirname(__FILE__) . '/stack/cas/cassession.class.php');
require_once(dirname(__FILE__) . '/stack/cas/keyval.class.php');


require_login();

$context = context_system::instance();
require_capability('moodle/site:config', $context);
$PAGE->set_context($context);
$PAGE->set_url('/question/type/stack/caschat.php');
$title = stack_string('chattitle');
$PAGE->set_title($title);


$debuginfo = '';
$errs = '';
$varerrs = '';

$vars   = optional_param('vars', '', PARAM_RAW);
$string = optional_param('cas', '', PARAM_RAW);
$simp   = optional_param('simp', '', PARAM_RAW);

// Sort out simplification.
if ('on' == $simp) {
    $simp = true;
} else {
    $simp = false;
}
// Initially simplification should be on.
if (!$vars and !$string) {
    $simp = true;
}

if ($string) {
    $options = new stack_options();
    $options->set_option('simplify', $simp);

    $session = new stack_cas_session(null, $options);
    if ($vars) {
        $keyvals = new stack_cas_keyval($vars, $options, 0, 't');
        $session = $keyvals->get_session();
        $varerrs = $keyvals->get_errors();
    }

    if (!$varerrs) {
        $ct           = new stack_cas_text($string, $session, 0, 't');
        $displaytext  = $ct->get_display_castext();
        $errs         = $ct->get_errors();
        $debuginfo    = $ct->get_debuginfo();
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading($title);
echo html_writer::tag('p', stack_string('chatintro'));

if (!$varerrs) {
    if ($string) {
        echo $OUTPUT->box(format_text($displaytext));
    }
}

if ($simp) {
    $simp = stack_string('autosimplify').' '.html_writer::empty_tag('input', array('type' => 'checkbox', 'checked' => $simp, 'name'=>'simp'));
} else {
    $simp = stack_string('autosimplify').' '.html_writer::empty_tag('input', array('type' => 'checkbox', 'name'=>'simp'));
}

$varlen = substr_count($vars, "\n")+3;
$stringlen = max(substr_count($string, "\n")+3,8);

echo html_writer::tag('form',
            html_writer::tag('h2', stack_string('questionvariables')) .
            html_writer::tag('p', $varerrs) .
            html_writer::tag('p', html_writer::tag('textarea', $vars,
                    array('cols' => 100, 'rows' => $varlen, 'name' => 'vars'))) .
            html_writer::tag('p', $simp) .
            html_writer::tag('h2', stack_string('castext')) .
            html_writer::tag('p', $errs) .
            html_writer::tag('p', html_writer::tag('textarea', $string,
                    array('cols' => 100, 'rows' => $stringlen, 'name' => 'cas'))) .
            html_writer::tag('p', html_writer::empty_tag('input',
                    array('type' => 'submit', 'value' => stack_string('chat')))),
        array('action' => $PAGE->url, 'method' => 'post'));

if ('' != trim($debuginfo)) {
    echo $OUTPUT->box($debuginfo);
}

echo $OUTPUT->footer();
