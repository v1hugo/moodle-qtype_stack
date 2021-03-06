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
 * This script helps  that the stack is installed correctly, and that
 * all the parts are working properly, including the conection to the CAS,
 * graph plotting, and equation rendering.
 *
 * @copyright  2012 University of Birmingham
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../../config.php');
require_once($CFG->dirroot .'/course/lib.php');
require_once($CFG->libdir .'/filelib.php');

require_once(dirname(__FILE__) . '/locallib.php');
require_once(dirname(__FILE__) . '/stack/utils.class.php');
require_once(dirname(__FILE__) . '/stack/options.class.php');
require_once(dirname(__FILE__) . '/stack/cas/castext.class.php');
require_once(dirname(__FILE__) . '/stack/cas/casstring.class.php');
require_once(dirname(__FILE__) . '/stack/cas/cassession.class.php');
require_once(dirname(__FILE__) . '/stack/cas/connector.dbcache.class.php');
require_once(dirname(__FILE__) . '/stack/cas/installhelper.class.php');


// Check permissions.
require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context);

// Set up page.
$PAGE->set_context($context);
$PAGE->set_url('/question/type/stack/healthcheck.php');
$title = stack_string('healthcheck');
$PAGE->set_title($title);

// Clear the cache if requested.
if (data_submitted() && optional_param('clearcache', false, PARAM_BOOL)) {
    require_sesskey();
    stack_cas_connection_db_cache::clear_cache($DB);
    redirect($PAGE->url);
}

// Some test data.
$sampletex = '\sum_{n=1}^\infty \frac{1}{n^2} = \frac{\pi^2}{6}.';
$samplecastext = 'The derivative of @ x^4/(1+x^4) @ is \[ \frac{d}{dx} \frac{x^4}{1+x^4} = @ diff(x^4/(1+x^4),x) @. \]';
$sampleplots = 'Two example plots below.  @plot([x^4/(1+x^4),diff(x^4/(1+x^4),x)],[x,-3,3])@  ' .
        '@plot([sin(x),x,x^2,x^3],[x,-3,3],[y,-3,3])@';

$config = get_config('qtype_stack');

// Start output.
echo $OUTPUT->header();
echo $OUTPUT->heading($title);

// LaTeX.
echo $OUTPUT->heading(stack_string('healthchecklatex'), 3);
echo html_writer::tag('p', stack_string('healthchecklatexintro'));

echo html_writer::tag('dt', stack_string('texdoubledollar'));
echo html_writer::tag('dd', format_text('$$' . $sampletex . '$$'));

echo html_writer::tag('dt', stack_string('texsingledollar'));
echo html_writer::tag('dd', format_text('$' . $sampletex . '$'));

echo html_writer::tag('dt', stack_string('texdisplayedbracket'));
echo html_writer::tag('dd', format_text('\[' . $sampletex . '\]'));

echo html_writer::tag('dt', stack_string('texinlinebracket'));
echo html_writer::tag('dd', format_text('\(' . $sampletex . '\)'));

echo html_writer::tag('p', stack_string('healthchecklatexmathjax', $CFG->wwwroot .
        '/' . $CFG->admin . '/settings.php?section=additionalhtml'));
$mathjaxcode = <<<END
<script type="text/x-mathjax-config">
MathJax.Hub.Config({
    MMLorHTML: { prefer: "HTML" },
    tex2jax: {
        displayMath: [['$$', '$$'], ['\\\\[', '\\\\]']],
        inlineMath:  [['$',  '$' ], ['\\\\(', '\\\\)']],
        processEscapes: true
    }
});
</script>
<script type="text/javascript" src="http://cdn.mathjax.org/mathjax/latest/MathJax.js?config=TeX-AMS_HTML"></script>
END;
echo html_writer::tag('textarea', s($mathjaxcode),
        array('readonly' => 'readonly', 'wrap' => 'virtual', 'rows'=>'10', 'cols'=>'100'));

// Maxima config.
echo $OUTPUT->heading(stack_string('healthcheckconfig'), 3);

// Check for location of Maxima.
$maxima_location = stack_cas_configuration::confirm_maxima_win_location();
if ('' != $maxima_location) {
    echo html_writer::tag('p', stack_string('healthcheckconfigintro1').' '.html_writer::tag('tt', $maxima_location));
}

// Try to connect to create maxima local.
echo html_writer::tag('p', stack_string('healthcheckconfigintro2'));
stack_cas_configuration::create_maximalocal();

echo html_writer::tag('textarea', stack_cas_configuration::generate_maximalocal_contents(),
        array('readonly' => 'readonly', 'wrap' => 'virtual', 'rows'=>'32', 'cols'=>'100'));

// Maxima config.
if (stack_cas_configuration::maxima_bat_is_missing()) {
    echo $OUTPUT->heading(stack_string('healthcheckmaximabat'), 3);
    echo html_writer::tag('p', stack_string('healthcheckmaximabatinfo', $CFG->dataroot));
}

// Test Maxima connection.
output_cas_text(stack_string('healthcheckconnect'),
        stack_string('healthcheckconnectintro'), $samplecastext);

// Test plots.
output_cas_text(stack_string('healthcheckplots'),
        stack_string('healthcheckplotsintro'), $sampleplots);

// State of the cache.
echo $OUTPUT->heading(stack_string('settingcasresultscache'), 3);
echo html_writer::tag('p', stack_string('healthcheckcache_' . $config->casresultscache));
if ('db' == $config->casresultscache) {
    echo html_writer::tag('p', stack_string('healthcheckcachestatus',
            stack_cas_connection_db_cache::entries_count($DB)));
    echo $OUTPUT->single_button(
            new moodle_url($PAGE->url, array('clearcache' => 1, 'sesskey' => sesskey())),
            stack_string('clearthecache'));
}

echo $OUTPUT->footer();


function output_cas_text($title, $intro, $castext) {
    global $OUTPUT;

    echo $OUTPUT->heading($title, 3);
    echo html_writer::tag('p', $intro);
    echo html_writer::tag('pre', s($castext));

    $ct = new stack_cas_text($castext, null, 0);

    echo html_writer::tag('p', format_text($ct->get_display_castext()));
    echo output_debug(stack_string('errors'), $ct->get_errors());
    echo output_debug(stack_string('debuginfo'), $ct->get_debuginfo());
}


function output_debug($title, $message) {
    global $OUTPUT;

    if (!$message) {
        return;
    }

    return $OUTPUT->box($OUTPUT->heading($title) . $message);
}
