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
 * A CAS session is a list of Maxima expressions, which are validated
 * sent to the CAS Maxima to be evaluated, and then used.  This class
 * prepares expressions for the CAS and deals with return information.
 *
 * @copyright  2012 The University of Birmingham
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('casstring.class.php');
require_once('connector.class.php');
require_once(dirname(__FILE__) . '/../options.class.php');


/**
 *  This deals with Maxima sessions.
 *  This is the class which actually sends variables to the CAS itself.
 */

class stack_cas_session {
    /**
     * @var array stack_cas_casstring
     */
    private $session;

    /**
     * @var stack_options
     */
    private $options;

    /**
     * @var int Needed to seed any randomization when instantated.
     */
    private $seed;

    /**
     * @var boolean
     */
    private $valid;

    /**
     * @var boolean Has this been sent to the CAS yet?
     */
    private $instantiated;

    /**
     * @var string Error messages for the user.
     */
    private $errors;

    /**
     * @var string
     */
    private $debuginfo;

    public function __construct($session, $options = null, $seed=null) {

        if (is_null($session)) {
            $session = array();
        }

        // An array of stack_cas_casstring.
        $this->session = $session;

        if ($options === null) {
            $this->options = new stack_options();
        } else if (is_a($options, 'stack_options')) {
            $this->options = $options;
        } else {
            throw new stack_exception('stack_cas_session: $options must be stack_options.');
        }

        if (!($seed === null)) {
            if (is_int($seed)) {
                $this->seed = $seed;
            } else {
                throw new stack_exception('stack_cas_session: $seed must be a number.  Got "'.$seed.'"');
            }
        } else {
            $this->seed = time();
        }
    }

    /*********************************************************/
    /* Validation functions                                  */
    /*********************************************************/

    private function validate() {
        if (null === $this->session) { // Empty sessions are ok.
            $this->valid = true;
            return true;
        }
        if (false === is_array($this->session)) {
            $this->valid = false;
            return false;
        }
        if (empty($this->session)) {
            $this->valid = true;
            $this->session = null;
            return true;
        }

        $this->valid = $this->validate_array($this->session);

        // Ensure the array is number ordered.  We use this later when getting back the values of expressions
        // so it important to be definite now.
        if ($this->valid) {
            $this->session = array_values($this->session);
        }
        return $this->valid;
    }

    /* A helper function which enables an array of stack_cas_casstring to be validated */
    private function validate_array($cmd) {
        $valid  = true;
        foreach ($cmd as $key => $val) {
            if (is_a($val, 'stack_cas_casstring')) {
                if (!$val->get_valid()) {
                    $valid = false;
                    $this->errors .= $val->get_errors();
                }
            } else {
                throw new stack_exception('stack_cas_session: $session must be null or an array of stack_cas_casstring.');
            }
        }
        return $valid;
    }

    /* Check each of the CASStrings for any of the keywords */
    public function check_external_forbidden_words($keywords) {
        if (null===$this->valid) {
            $this->validate();
        }
        $found = false;
        foreach ($this->session as $casstr) {
            $found = $found || $casstr->check_external_forbidden_words($keywords);
        }
        return $found;
    }

    /* This is the function which actually sends the commands off to Maxima. */
    public function instantiate() {
        if (null===$this->valid) {
            $this->validate();
        }
        if (!$this->valid) {
            return false;
        }
        // Lazy instantiation - only do this once...
        // Empty session.  Nothing to do.
        if ($this->instantiated || null===$this->session) {
            return true;
        }

        $connection = stack_cas_connection_base::make();
        $results = $connection->compute($this->construct_maxima_command());
        $this->debuginfo = $connection->get_debuginfo();

        // Now put the information back into the correct slots.
        $session = $this->session;
        $new_session = array();
        $new_errors  = '';
        $all_fail = true;
        $i=0;

        // We loop over each entry in the session, not over the result.
        // This way we can add an error for missing values.
        foreach ($session as $cs) {
            $gotvalue = false;

            if ('' ==  $cs->get_key()) {
                $key = 'dumvar'.$i;
            } else {
                $key = $cs->get_key();
            }

            if (array_key_exists($i, $results)) {
                $all_fail = false; // We at least got one result back from the CAS!

                $result = $results["$i"]; // GOCHA!  results have string represenations of numbers, not int....

                if (array_key_exists('value', $result)) {
                    $val = str_replace('QMCHAR', '?', $result['value']);
                    $cs->set_value($val);
                    $gotvalue = true;
                }

                if (array_key_exists('display', $result)) {
                    $cs->set_display($result['display']);
                }

                if (array_key_exists('valid', $result)) {
                    $cs->set_valid($result['valid']);
                }

                if (array_key_exists('answernote', $result)) {
                    $cs->set_answernote($result['answernote']);
                }

                if (array_key_exists('feedback', $result)) {
                    $cs->set_feedback($result['feedback']);
                }

                if ('' != $result['error']) {
                    // This protects dolar signs so they are not interpreted as LaTeX maths environment starts.
                    $err = str_replace('$', '\$', $result['error']);
                    $cs->add_errors($err);
                    $new_errors .= stack_maxima_format_casstring($cs->get_raw_casstring());
                    $new_errors .= ' '.stack_string("stackCas_CASErrorCaused").' '.$err.' ';
                }
            }

            if (!$gotvalue) {
                $errstr = stack_string("stackCas_failedReturn").' '.stack_maxima_format_casstring($cs->get_raw_casstring());
                $cs->add_errors($errstr);
                $new_errors .= $errstr;
            }

            $new_session[]=$cs;
            $i++;
        }
        $this->session = $new_session;

        if (''!= $new_errors) {
            $this->errors .= '<span class="error">'.stack_string('stackCas_CASError').'</span>'.$new_errors;
        }
        if ($all_fail) {
            $this->errors = '<span class="error">'.stack_string('stackCas_allFailed').'</span>';
        }

        $this->instantiated = true;
    }

    public function get_debuginfo() {
        return $this->debuginfo;
    }

    /**
     * Add extra variables to the end of the existing session.
     * Note that this resets instantiation and validation, which will need to be
     * done again if used.
     * @param array $vars variable name => stack_cas_casstring, the variables to add.
     */
    public function add_vars($vars) {
        if (!is_array($vars)) {
            return;
        }
        foreach ($vars as $var) {
            if (!is_a($var, 'stack_cas_casstring')) {
                throw new stack_exception('stack_cas_session: trying to add a non-stack_cas_casstring to an existing session.');
            }

            $this->instantiated = null;
            $this->errors       = null;
            $this->session[]    = clone $var; // Yes, we reall need new versions of the variables.
        }
    }

    /**
     * Concatenates the variables from $incoming onto the end of $this->session
     * Treats this as essentially a new session
     * The settings for this session are respected (currently)
     * @param stack_cas_session $incoming
     */
    public function merge_session($incoming) {
        if (null===$incoming) {
            return true;
        }
        if (is_a($incoming, 'stack_cas_session')) {
            $this->add_vars($incoming->get_session()); // This method resets errors and instantiated fields.
            $this->valid        = null;
        } else {
            throw new stack_exception('stack_cas_session: merge_session expects its argument to be a stack_cas_session');
        }
    }

    /*********************************************************/
    /* Return and modify information                         */
    /*********************************************************/

    public function get_valid() {
        if (null===$this->valid) {
            $this->validate();
        }
        return $this->valid;
    }

    public function get_errors($casdebug=false) {
        if (null===$this->valid) {
            $this->validate();
        }
        if ($casdebug) {
            return $this->errors.$this->get_debuginfo();
        }
        return $this->errors;
    }

    public function get_all_raw_casstrings() {
        $return = array();
        if (!(null === $this->session)) { // Empty sessions are ok.
            foreach ($this->session as $casstr) {
                $return[] = $casstr->get_raw_casstring();
            }
        }
        return $return;
    }

    public function get_casstring_key($key) {
        if (null===$this->valid) {
            $this->validate();
        }
        foreach (array_reverse($this->session) as $casstr) {
            if ($casstr->get_key()===$key) {
                return $casstr->get_casstring();
            }
        }
        return false;
    }

    public function get_value_key($key) {
        if (null===$this->valid) {
            $this->validate();
        }
        if ($this->valid && null===$this->instantiated) {
            $this->instantiate();
        }
        // We need to reverse the array to get the last value with this key.
        foreach (array_reverse($this->session) as $casstr) {
            if ($casstr->get_key()===$key) {
                return $casstr->get_value();
            }
        }
        return false;
    }

    public function get_display_key($key) {
        if (null===$this->valid) {
            $this->validate();
        }
        if ($this->valid && null === $this->instantiated) {
            $this->instantiate();
        }
        foreach (array_reverse($this->session) as $casstr) {
            if ($casstr->get_key()===$key) {
                return $casstr->get_display();
            }
        }
        return false;
    }

    public function get_errors_key($key) {
        if (null===$this->valid) {
            $this->validate();
        }
        if ($this->valid && null === $this->instantiated) {
            $this->instantiate();
        }
        foreach (array_reverse($this->session) as $casstr) {
            if ($casstr->get_key()===$key) {
                return $casstr->get_errors();
            }
        }
        return false;
    }

    public function get_session() {
        return $this->session;
    }

    public function prune_session($len) {
        if (!is_int($len)) {
            throw new stack_exception('stack_cas_session: prune_session $len must be an integer.');
        }
        $new_session = array_slice($this->session, 0, $len);
        $this->session = $new_session;
    }

    public function get_all_keys() {
        if (null===$this->valid) {
            $this->validate();
        }

        $keys = array();
        if (empty($this->session)) {
            return array();
        }
        foreach ($this->session as $cs) {
            $keys[$cs->get_key()] = true;
        }
        $keys = array_keys($keys);
        return $keys;
    }

    /* This returns the values of the variables with keys */
    public function get_display_castext($strin) {
        if (null===$this->valid) {
            $this->validate();
        }
        if ($this->valid && null === $this->instantiated) {
            $this->instantiate();
        }
        if (null === $this->session) {
            return $strin;
        }

        foreach ($this->session as $casstr) {
            $key    = $casstr->get_key();
            $errors = $casstr->get_errors();
            $disp   = $casstr->get_display();
            $value  = $casstr->get_casstring();

            $dummy = '@'.$key.'@';

            if (''!==$errors && null!=$errors) {
                $strin = str_replace($dummy, $value, $strin);
            } else if (strstr($strin, $dummy)) {
                $strin = str_replace($dummy, $disp, $strin);
            }
        }
        return $strin;
    }

    /**
     * Creates the string which Maxima will execute
     *
     * @return string
     */
    private function construct_maxima_command() {
        // Ensure that every command has a valid key.

        $cas_options = $this->options->get_cas_commands();

        $csnames = $cas_options['names'];
        $csvars  = $cas_options['commands'];
        $cascommands= '';

        $i=0;
        foreach ($this->session as $cs) {
            if ('' ==  $cs->get_key()) {
                $label = 'dumvar'.$i;
            } else {
                $label = $cs->get_key();
            }

            // Replace any ?'s with a safe value.
            $cmd = str_replace('?', 'QMCHAR', $cs->get_casstring());

            $csnames   .= ", $label";
            $cascommands .= ", print(\"$i=[ error= [\"), cte(\"$label\",errcatch($label:$cmd)) ";
            $i++;
        }

        $cass ='cab:block([ RANDOM_SEED';
        $cass .= $csnames;
        $cass .='], stack_randseed(';
        $cass .= $this->seed.')'.$csvars;
        $cass .= ", print(\"[TimeStamp= [ $this->seed ], Locals= [ \") ";
        $cass .= $cascommands;
        $cass .= ", print(\"] ]\") , return(true) ); \n ";

        return $cass;
    }

    /**
     * Creates a string which we can feedback into a keyval.class object.
     * This is sufficient to define the session for caching purposes.
     *
     * @return string
     */
    public function get_keyval_representation() {
        $keyvals = '';
        foreach ($this->session as $cs) {
            if (null === $this->instantiated) {
                $val = $cs->get_casstring();
            } else {
                $val = $cs->get_value();
            }

            if ('' == $cs->get_key()) {
                $keyvals .= $val.'; ';
            } else {
                $keyvals .= $cs->get_key().':'.$val.'; ';
            }
        }
        return trim($keyvals);
    }
}
