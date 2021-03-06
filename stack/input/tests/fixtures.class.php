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
 * This script runs the student input tests and verifies the results.
 *
 * This helps us verify how STACK "validates" strings supplied by the student.
 *
 * @copyright  2012 University of Birmingham
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


class stack_inputvalidation_test_data {
    const RAWSTRING     = 0;
    const PHPVALID      = 1;
    const PHPCASSTRING  = 2;
    const CASVALID      = 3;
    const NOTES         = 4;

    protected static $rawdata = array(
        array('x', 'php_true', 'x', 'cas_true', "Whitespace"),
        array('xy', 'php_true', 'xy', 'cas_true', "This is a single variable name, not a product."),
        array('x+1', 'php_true', 'x+1', 'cas_true', ""),
        array('x+ 1', 'php_true', 'x+ 1', 'cas_true', ""),
        array('x + 1', 'php_true', 'x + 1', 'cas_true', "Ok to have some spaces between these operators."),
        array('sin x', 'php_false', '', '', "We don't allow spaces to denote function application. A Maxima restriction."),
        array('x y', 'php_false', '', '', "We don't allow spaces to denote implicit multiplication."),
        array('1 x', 'php_false', '', '', ""),
        array('1x', 'php_true', '1*x', 'cas_true', ""),
        array('x1', 'php_true', 'x*1', 'cas_true', ""),
        array('1', 'php_true', '1', 'cas_true', "Numbers"),
        array('.1', 'php_true', '.1', 'cas_true', "This is an option."),
        array('1/2', 'php_true', '1/2', 'cas_true', ""),
        array('2/4', 'php_true', '2/4', 'cas_true', "Rejecting this as 'invalid' not 'wrong' is a question option."),
        array('1/0', 'php_true', '1/0', 'cas_true', ""),
        array('pi', 'php_true', 'pi', 'cas_true', ""),
        array('e', 'php_true', 'e', 'cas_true', "Cannot easily make \(e\) a variable name."),
        array('i', 'php_true', 'i', 'cas_true', "Options to make i a variable, or a vector unit.  Note this is not italic."),
        array('j', 'php_true', 'j', 'cas_true',
            "Can define \(j^2=-1\) as an option, or a vector unit.  By default a variable, so italic."),
        array('%pi', 'php_true', '%pi', 'cas_true', ""),
        array('%e', 'php_true', '%e', 'cas_true', ""),
        array('%i', 'php_true', '%i', 'cas_true', ""),
        array('1E+3', 'php_true', '1*E+3', 'cas_true', "Scientific notation - does not work when strict syntax is false."),
        array('3E2', 'php_true', '3*E*2', 'cas_true', ""),
        array('1+i', 'php_true', '1+i', 'cas_true', ""),
        array('3-i', 'php_true', '3-i', 'cas_true', ""),
        array('-3+i', 'php_true', '-3+i', 'cas_true', ""),
        array('1+2i', 'php_true', '1+2*i', 'cas_true', ""),
        array('true', 'php_true', 'true', 'cas_true', "Booleans"),
        array('false', 'php_true', 'false', 'cas_true', ""),
        array('"1+1"', 'php_true', '"1+1"', 'cas_true',
        "Strings - generally discouraged in STACK.  Note, this is a string within a mathematical expression, not literally 1+1."),
        array('"Hello world"', 'php_false', '', '',
        "Currently strings must pass the security checks.   We could disable this,
        but since strings are not encouraged we keep it for now."),
        array('x', 'php_true', 'x', 'cas_true', "Names for variables etc."),
        array('a1', 'php_true', 'a*1', 'cas_true', ""),
        array('a9b', 'php_true', 'a*9*b', 'cas_true', "Note the subscripting and the implied multiplication."),
        array("a'", 'php_false', '', '', ""),
        array('X', 'php_true', 'X', 'cas_true', ""),
        array('aXy1', 'php_true', 'aXy1', 'cas_true', ""),
        array('f(x)', 'php_true', 'f*(x)', 'cas_true', "Functions"),
        array('a(x)', 'php_true', 'a*(x)', 'cas_true', ""),
        array("f''(x)", 'php_false', '' , '', "Apostrophies again..."),
        array('dosomething(x,y,z)', 'php_false', '', '',
        "Students have a restricted list of function names.  Teachers are less restricted."),
        array('[]', 'php_true', '[]', 'cas_true', "Lists"),
        array('[1]', 'php_true', '[1]', 'cas_true', ""),
        array('[1,2,3.4]', 'php_true', '[1,2,3.4]', 'cas_true', ""),
        array('["a"]', 'php_true', '["a"]', 'cas_true', ""),
        array('[1,true,"a"]', 'php_true', '[1,true,"a"]', 'cas_true', ""),
        array('[[1,2],[3,4]]', 'php_true', '[[1,2],[3,4]]', 'cas_true', ""),
        array('(a,b,c)', 'php_true', '(a,b,c)', 'cas_true',
        "In Maxima this syntax is a programme block which returns its last element."),
        array('0..1', 'php_true', '0..1', 'cas_false',
        "Ranges and logical operations are currently not supported by Maxima or STACK
        - this is on our wish list. It will result in the ability to deal with systems of inequalities, e.g. \(x<1\ and\ x>-4\)."),
        array('0.1..1.2', 'php_true', '0.1..1.2', 'cas_false', ""),
        array('not x', 'php_false', '', '', ""),
        array('x and y', 'php_false', '', '', ""),
        array('x or y', 'php_false', '', '', ""),
        array('x xor y', 'php_false', '', '', ""),
        array('x isa "number"', 'php_false', '', '', ""),
        array('x && y', 'php_true', 'x && y', '', ""),
        array('x || y', 'php_true', 'x || y', '', ""),
        array('x | y', 'php_true', 'x | y', '', ""),
        array('x * y', 'php_true', 'x * y', 'cas_true',
        "Operations: there are options on how this is displayed, either as \(x\cdot y\), \(x\\times y\), or as \(x\, y\)."),
        array('x + y', 'php_true', 'x + y', 'cas_true', ""),
        array('x - y', 'php_true', 'x - y', 'cas_true', ""),
        array('x / y', 'php_true', 'x / y', 'cas_true', ""),
        array('x ^ y', 'php_true', 'x ^ y', 'cas_true', ""),
        array('x < y', 'php_true', 'x < y', 'cas_true', ""),
        array('x > y', 'php_true', 'x > y', 'cas_true', ""),
        array('x = y', 'php_true', 'x = y', 'cas_true', ""),
        array('x!', 'php_true', 'x!', 'cas_true', ""),
        array('!x', 'php_true', '!x', 'cas_false', ""),
        array('x_1', 'php_true', 'x_1', 'cas_true', ""),
        array('x <= y', 'php_true', 'x <= y', 'cas_true',
        "Only single inequalities are currently acceptable."),
        array('x >= y', 'php_true', 'x >= y', 'cas_true', ""),
        array('x <> y', 'php_true', 'x <> y', 'cas_false', "This isn't permitted in Maxima"),
        array('x+', 'php_false', 'x+', '', "Not enough arguments for op error"),
        array('y*', 'php_false', 'y*', '', ""),
        array('x^', 'php_flase', 'x^', '', ""),
        array('x and', 'php_false', '', '', ""),
        array('!', 'php_true', '!', '', ""),
        array('sin', 'php_true', 'sin', 'cas_true',
        "This names the operator sine, which is a valid expression on its own.
        The classic difference between the function \(f\) and the value of the
        function at a point \(f(x)\).  Maybe a 'gocha' for the question author...."),
        array('(x+y)^z', 'php_true', '(x+y)^z', 'cas_true',
        "Check display: brackets only go round operands when strictly necessary, but student validation respects the input."),
        array('x+(y^z)', 'php_true', 'x+(y^z)', 'cas_true', ""),
        array('x-(y+z)', 'php_true', 'x-(y+z)', 'cas_true', ""),
        array('(x-y)+z', 'php_true', '(x-y)+z', 'cas_true', ""),
        array('x^(-(y+z))', 'php_true', 'x^(-(y+z))', 'cas_true', ""),
        array('x^(-y)', 'php_true', 'x^(-y)', 'cas_true', ""),
        array('x^-y', 'php_true', 'x^-y', 'cas_true', ""),
        array('x^(y+z)', 'php_true', 'x^(y+z)', 'cas_true', ""),
        array('(1+i)*x', 'php_true', '(1+i)*x', 'cas_true', ""),
        array('(1+i)+x', 'php_true', '(1+i)+x', 'cas_true', ""),
        array('(x)', 'php_true', '(x)', 'cas_true', "Brackets"),
        array('((x))', 'php_true', '((x))', 'cas_true', ""),
        array('(()x)', 'php_false', '(()*x)', 'cas_false', ""),
        array('()x', 'php_false', '()*x', 'cas_false', ""),
        array('x()', 'php_false', 'x*()', 'cas_false', ""),
        array('(', 'php_false', '', '', "Brackets"),
        array(')', 'php_false', '', '', ""),
        array('[', 'php_false', '', '', ""),
        array(']', 'php_false', '', '', ""),
        array('{', 'php_false', '', '', ""),
        array('}', 'php_false', '', '', ""),
        array('x)', 'php_false', '', '', ""),
        array('(x', 'php_false', '', '', ""),
        array('(x+(y)', 'php_false', '', '', ""),
        array('x+(y))', 'php_false', '', '', ""),
        array('f(x))', 'php_false', '', '', ""),
        array('[x', 'php_false', '', '', ""),
        array('x]', 'php_false', '', '', ""),
        array('{x', 'php_false', '', '', ""),
        array('alpha', 'php_true', 'alpha', 'cas_true',
        "Greek letters - quite a few have meanings in Maxima already."),
        array('beta', 'php_true', 'beta', 'cas_true',
        "The beta function is defined as $\gamma(a) \gamma(b)/\gamma(a+b)$."),
        array('gamma', 'php_true', 'gamma', 'cas_true', "This is the gamma function."),
        array('delta', 'php_true', 'delta', 'cas_true', "This is the Dirac Delta function."),
        array('epsilon', 'php_true', 'epsilon', 'cas_true', ""),
        array('zeta', 'php_true', 'zeta', 'cas_true', "This is the Riemann zeta function."),
        array('eta', 'php_true', 'eta', 'cas_true', ""),
        array('theta', 'php_true', 'theta', 'cas_true', ""),
        array('iota', 'php_true', 'iota', 'cas_true', ""),
        array('kappa', 'php_true', 'kappa', 'cas_true', ""),
        array('lambda', 'php_true', 'lambda', 'cas_true', "Defines and returns a lambda expression."),
        array('mu', 'php_true', 'mu', 'cas_true', ""),
        array('nu', 'php_true', 'nu', 'cas_true', ""),
        array('xi', 'php_true', 'xi', 'cas_true', ""),
        array('omicron', 'php_true', 'omicron', 'cas_true', ""),
        array('pi', 'php_true', 'pi', 'cas_true', "This is a numeric constant."),
        array('rho', 'php_true', 'rho', 'cas_true', ""),
        array('sigma', 'php_true', 'sigma', 'cas_true', ""),
        array('tau', 'php_true', 'tau', 'cas_true', ""),
        array('upsilon', 'php_true', 'upsilon', 'cas_true', ""),
        array('phi', 'php_true', 'phi', 'cas_true', "Constant, represents the so-called golden mean, $(1 + \sqrt{5})/2$."),
        array('chi', 'php_true', 'chi', 'cas_true', ""),
        array('psi', 'php_true', 'psi', 'cas_true', "The derivative of \(\log (\gamma (x))\) of order \(n+1\)."),
        array('omega', 'php_true', 'omega', 'cas_true', ""),
        array('(x+2)3', 'php_true', '(x+2)*3', 'cas_true', "Implicit multiplication"),
        array('(x+2)y', 'php_true', '(x+2)*y', 'cas_true', ""),
        array('3(x+1)', 'php_true', '3*(x+1)', 'cas_true', ""),
        array('x(2+1)', 'php_true', 'x*(2+1)', 'cas_true', ""),
        array('(x+2)(x+3)', 'php_true', '(x+2)*(x+3)', 'cas_true', ""),
        array('f(x)(2)', 'php_true', 'f*(x)*(2)', 'cas_true', ""),
        array('xsin(1)', 'php_false', '', '',
        "single-letter variable name followed by known function is an implicit multiplication"),
        array('ycos(2)', 'php_false', '', '', ""),
        array('Bgcd(3,2)', 'php_false', '', '', ""),
        array('+1', 'php_true', '+1', 'cas_true', "Unary plus"),
        array('+0.2', 'php_true', '+0.2', 'cas_true', ""),
        array('+e', 'php_true', '+e', 'cas_true', ""),
        array('+pi', 'php_true', '+pi', 'cas_true', ""),
        array('+i', 'php_true', '+i', 'cas_true', ""),
        array('+x', 'php_true', '+x', 'cas_true', ""),
        array('"+"(a,b)', 'php_true', '"+"(a,b)', 'cas_true', "This is Maxima specific syntax."),
        array('(+1)', 'php_true', '(+1)', 'cas_true', ""),
        array('[1,+2]', 'php_true', '[1,+2]', 'cas_true', ""),
        array('[+1,+2]', 'php_true', '[+1,+2]', 'cas_true', ""),
        array('-1', 'php_true', '-1', 'cas_true', "Unary minus"),
        array('-0.2', 'php_true', '-0.2', 'cas_true', ""),
        array('-e', 'php_true', '-e', 'cas_true', ""),
        array('-pi', 'php_true', '-pi', 'cas_true', ""),
        array('-i', 'php_true', '-i', 'cas_true', ""),
        array('-x', 'php_true', '-x', 'cas_true', ""),
        array('(-1)', 'php_true', '(-1)', 'cas_true', ""),
        array('[-1,-2]', 'php_true', '[-1,-2]', 'cas_true', ""),
        array('[1,-2]', 'php_true', '[1,-2]', 'cas_true', ""),
        array('x & y', 'php_true', 'x & y', '', "Synonyms"),
        array('x && y', 'php_true', 'x && y', '', ""),
        array('x and y', 'php_false', '', '', ""),
        array('x divides y', 'php_false', '', '', ""),
        array('x | y', 'php_true', 'x | y', '', ""),
        array('x or y', 'php_false', '', '', ""),
        array('x || y', 'php_false', 'x || y', 'cas_true', ""),
        array('sqr(x)', 'php_true', 'sqr(x)', 'cas_true', ""),
        array('sqrt(x)', 'php_true', 'sqrt(x)', 'cas_true', "There is an option to display this as \(x^{1/2}|\)."),
        array('gcf(x,y)', 'php_true', 'gcf(x,y)', 'cas_true', ""),
        array('gcd(x,y)', 'php_true', 'gcd(x,y)', 'cas_true', "Don't understand why this is evaluated by Maxima..."),
        array('sgn(x)', 'php_true', 'sgn(x)', 'cas_true', ""),
        array('sign(x)', 'php_true', 'sign(x)', 'cas_true', ""),
        array('len(x)', 'php_true', 'len(x)', 'cas_true', ""),
        array('abs(x)', 'php_true', 'abs(x)', 'cas_true', ""),
        array('|x|', 'php_true', '|x|', 'cas_true', ""),
        array('length(x)', 'php_true', 'length(x)', 'cas_true', ""),
        array('x^y^z', 'php_true', 'x^y^z', 'cas_true', "Associativity"),
        array('a/b/c', 'php_true', 'a/b/c', 'cas_true', ""),
        array('a-(b-c)', 'php_true', 'a-(b-c)', 'cas_true', ""),
        array('(a-b)-c', 'php_true', '(a-b)-c', 'cas_true', ""),
        array('x*y*z', 'php_true', 'x*y*z', 'cas_true', "Commutativity"),
        array('(x*y)*z', 'php_true', '(x*y)*z', 'cas_true', ""),
        array('x*(y*z)', 'php_true', 'x*(y*z)', 'cas_true', ""),
        array('x+y+z', 'php_true', 'x+y+z', 'cas_true', ""),
        array('(x+y)+z', 'php_true', '(x+y)+z', 'cas_true', ""),
        array('x+(y+z)', 'php_true', 'x+(y+z)', 'cas_true', ""),
        array('x/y/z', 'php_true', 'x/y/z', 'cas_true', ""),
        array('(x/y)/z', 'php_true', '(x/y)/z', 'cas_true', ""),
        array('x/(y/z)', 'php_true', 'x/(y/z)', 'cas_true', ""),
        array('x^y', 'php_true', 'x^y', 'cas_true', "Operations and functions with special TeX"),
        array('x^(y+z)', 'php_true', 'x^(y+z)', 'cas_true', ""),
        array('x^(y/z)', 'php_true', 'x^(y/z)', 'cas_true', ""),
        array('x^f(x)', 'php_true', 'x^f(x)', 'cas_true', ""),
        array('x*y^z', 'php_true', 'x*y^z', 'cas_true', ""),
        array('y^z * x', 'php_true', 'y^z * x', 'cas_true', ""),
        array('x*2^y', 'php_true', 'x*2^y', 'cas_true', ""),
        array('2^y*x', 'php_true', '2^y*x', 'cas_true', ""),
        array('2*pi', 'php_true', '2*pi', 'cas_true', ""),
        array('2*e', 'php_true', '2*e', 'cas_true', ""),
        array('e*2', 'php_true', 'e*2', 'cas_true', ""),
        array('pi*2', 'php_true', 'pi*2', 'cas_true', ""),
        array('i*2', 'php_true', 'i*2', 'cas_true', ""),
        array('2*i', 'php_true', '2*i', 'cas_true', ""),
        array('2*i^3', 'php_true', '2*i^3', 'cas_true', ""),
        array('x*i^3', 'php_true', 'x*i^3', 'cas_true', ""),
        array('x*(-y)', 'php_true', 'x*(-y)', 'cas_true', ""),
        array('(-x)*y', 'php_true', '(-x)*y', 'cas_true', ""),
        array('abs(13)', 'php_true', 'abs(13)', 'cas_true', ""),
        array('fact(13)', 'php_true', 'fact(13)', 'cas_true', ""),
        array('ceiling(x)', 'php_true', 'ceiling(x)', 'cas_true', ""),
        array('floor(x)', 'php_true', 'floor(x)', 'cas_true', ""),
        array('int(x,y)', 'php_true', 'int(x,y)', 'cas_true', ""),
        array('diff(x,y)', 'php_true', 'diff(x,y)', 'cas_true', ""),
        array("'int(x,y)", 'php_false', '', 'cas_true', "Note the use of the apostrophe here to make an inert function."),
        array("'diff(x,y)", 'php_false', '', 'cas_true', "Not ideal...arises because we don't 'simplify'."),
        array('partialdiff(x,y,1)', 'php_true', 'partialdiff(x,y,1)', 'cas_true', ""),
        array('limit(y,x,3)', 'php_true', 'limit(y,x,3)', 'cas_true', ""),
        array('mod(x,y)', 'php_true', 'mod(x,y)', 'cas_true', ""),
        array('perm(x,y)', 'php_true', 'perm(x,y)', 'cas_true', ""),
        array('comb(x,y)', 'php_true', 'comb(x,y)', 'cas_true', ""),
        array('root(3,2)', 'php_true', 'root(3,2)', 'cas_true', ""),
        array('switch(x,a,y,b,c)', 'php_false', '', '', ""),
        array('sin(x)', 'php_true', 'sin(x)', 'cas_true', "Trig functions"),
        array('cos(x)', 'php_true', 'cos(x)', 'cas_true', ""),
        array('tan(x)', 'php_true', 'tan(x)', 'cas_true', ""),
        array('sec(x)', 'php_true', 'sec(x)', 'cas_true', ""),
        array('cot(x)', 'php_true', 'cot(x)', 'cas_true', ""),
        array('cosec(x)', 'php_true', 'cosec(x)', 'cas_true', ""),
        array('asin(x)', 'php_true', 'asin(x)', 'cas_true', "Maxima uses the asin pattern"),
        array('arcsin(x)', 'php_true', 'arcsin(x)', 'cas_true', "Not the arcsin"),
        array('sin^-1(x)', 'php_true', 'sin^-1(x)', 'cas_true',
        "WARNING: look carefully.  Probably not what the student expects....."),
        array('cosh(x)', 'php_true', 'cosh(x)', 'cas_true', ""),
        array('sinh(x)', 'php_true', 'sinh(x)', 'cas_true', ""),
        array('tanh(x)', 'php_true', 'tanh(x)', 'cas_true', ""),
        array('coth(x)', 'php_true', 'coth(x)', 'cas_true', ""),
        array('cosech(x)', 'php_true', 'cosech(x)', 'cas_true', ""),
        array('sech(x)', 'php_true', 'sech(x)', 'cas_true', ""),
        array('asinh(x)', 'php_true', 'asinh(x)', 'cas_true', "Etc..."),
        array('a^b', 'php_true', 'a^b', 'cas_true', "Exponentials and logarithms"),
        array('a ** b', 'php_true', 'a ** b', 'cas_true', ""),
        array('x^-1', 'php_true', 'x^-1', 'cas_true', ""),
        array('a^-b', 'php_true', 'a^-b', 'cas_true', ""),
        array('e^x', 'php_true', 'e^x', 'cas_true', ""),
        array('%e^x', 'php_true', '%e^x', 'cas_true', ""),
        array('exp(x)', 'php_true', 'exp(x)', 'cas_true', ""),
        array('log(x)', 'php_true', 'log(x)', 'cas_true', "Natural logarithm."),
        array('ln(x)', 'php_true', 'ln(x)', 'cas_true', "Natural logarithm, STACK alias."),
        array('log10(x)', 'php_true', 'log10(x)', 'cas_true', "BUG!  Should be logarithm to the base $10$."),
        array('lg(x)', 'php_true', 'lg(x)', 'cas_true', "Logarithm to the base $10$."),
        array('a++b', 'php_true', 'a++b', 'cas_true', "The extra plusses or minuses are interpreted as unary operators on b"),
        array('a +++ b', 'php_true', 'a +++ b', 'cas_true', ""),
        array('a --- b', 'php_true', 'a --- b', 'cas_true', ""),
        array('a,b,c', 'php_true', 'a,b,c', 'cas_true', "The following are known to fail.  Some are bugs...."),
        array('x_y', 'php_true', 'x_y', 'cas_true', ""),
        array('([x)]', 'php_false', '([x)]', '', ""),
        array('if(x,y,z)', 'php_true', 'if(x,y,z)', 'cas_true', ""),
        array('log(2x)/x+1/2', 'php_true', 'log(2*x)/x+1/2', 'cas_true', ""),
        );

    public static function get_raw_test_data() {
        return self::$rawdata;
    }

    public static function test_from_raw($data) {
        $test = new stdClass();
        $test->rawstring     = $data[self::RAWSTRING];
        $test->phpvalid      = $data[self::PHPVALID];
        $test->phpcasstring  = $data[self::PHPCASSTRING];
        $test->casvalid      = $data[self::CASVALID];
        $test->notes         = $data[self::NOTES];
        return $test;
    }

    public static function get_all() {
        $tests = array();
        foreach (self::$rawdata as $data) {
            $tests[] = self::test_from_raw($data);
        }
        return $tests;
    }

    public static function run_test($test) {
        // Note: What we would really like to do is
        // $el = stack_input_factory::make('algebraic', 'sans1', 'x');
        // $el->set_parameter('insertStars', true);
        // $el->set_parameter('strictSyntax', false);
        // $el->set_parameter('sameType', false);
        // $cs = $el->validate_student_response($test->rawstring);
        // but we want to pull apart the bits to expose where the various errors occur.

        $cs= new stack_cas_casstring($test->rawstring);
        $cs->validate('s', false, true);
        $cs->set_cas_validation_casstring('sans1', true, true, null);

        $phpvalid = $cs->get_valid();
        if ($phpvalid) {
            // Trim off stack_validate_typeless([..], true, true).
            $phpcasstring = $cs->get_casstring();
            $phpcasstring = substr($cs->get_casstring(), 25, strlen($cs->get_casstring())-37);
            $phpcasstring = substr($phpcasstring, -13);
            $outputphpcasstring = $phpcasstring;
        } else {
            $phpcasstring = '';
            $outputphpcasstring = 'N/A...';
        }

        $errors   = $cs->get_errors();

        if ('php_true'==$test->phpvalid) {
            $expected = true;
        } else {
            $expected = false;
        }

        $passed = true;
        if ($phpvalid != $expected) {
            $passed = false;
            $errors .= ' '.stack_string('phpvalidatemismatch');
        }
        if ($phpvalid && $phpcasstring != $test->phpcasstring) {
            $passed = false;
            $errors .= ' ' . stack_maxima_format_casstring($phpcasstring) .
                    ' \(\neq \) '.stack_maxima_format_casstring($test->phpcasstring);
        }

        $casvalid = '';
        $caserrors = '';
        $casvalue = '';
        $casdisplay = '';
        if ($cs->get_valid()) {
            $options = new stack_options();
            $options->set_option('simplify', false);

            $session = new stack_cas_session(array($cs), $options, 0);
            $session->instantiate();
            $session = $session->get_session();
            $cs = $session[0];
            $caserrors = stack_maxima_translate($cs->get_errors());
            $casvalue = stack_maxima_format_casstring($cs->get_value());
            if ('cas_true'==$test->casvalid) {
                $casexpected = true;
            } else {
                $casexpected = false;
            }
            if ('' == $cs->get_value()) {
                $casvalid = false;
            } else {
                $casvalid = true;
            }
            if ($casexpected != $casvalid) {
                $passed = false;
                $caserrors .= ' '.stack_string('casvalidatemismatch');
            }
            $casdisplay = $cs->get_display();
        }

        return array($passed, $phpvalid, $phpcasstring, $errors, $casvalid, $caserrors, $casdisplay, $casvalue);
    }
}