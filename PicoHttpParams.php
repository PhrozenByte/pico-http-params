<?php

/**
 * Pico HTTP params plugin - access HTTP GET and POST params in themes
 *
 * PicoHttpParams allows theme developers to access HTTP GET and HTTP POST
 * paramaters in Twig templates using the `url_param` resp. `form_param`
 * functions. This makes developing awesome themes for your Pico website
 * easier than ever before.
 *
 * This plugin is a backport of an feature that will be included in Pico's
 * core starting with Pico 1.1. To cut a long story short: Manually installing
 * this plugin makes sense with Pico 1.0 only.
 *
 * @author  Daniel Rudolf
 * @link    http://picocms.org
 * @license http://opensource.org/licenses/MIT The MIT License
 * @version 1.0.1
 */
class PicoHttpParams extends AbstractPicoPlugin
{
    /**
     * Silently disable this plugin to prevent conflicts with >= Pico 1.1,
     * as this is already part of Pico's core functionality in later releases
     *
     * @var boolean
     */
    protected $disabledSilently = false;

    /**
     * Silently disable this plugin with >= Pico 1.1
     *
     * @see DummyPlugin::onPluginsLoaded()
     */
    public function onPluginsLoaded(array &$plugins)
    {
        // Pico::VERSION wasn't defined in Pico 1.0
        if (defined('Pico::VERSION')) {
            $this->disabledSilently = true;
        }
    }

    /**
     * Register Twig's url_param and form_param functions
     *
     * @see DummyPlugin::onPageRendering()
     */
    public function onPageRendering(Twig_Environment &$twig, array &$twigVariables, &$templateName)
    {
        if (!$this->disabledSilently) {
            $twig->addFunction(new Twig_SimpleFunction('url_param', array($this, 'getUrlParameter')));
            $twig->addFunction(new Twig_SimpleFunction('form_param', array($this, 'getFormParameter')));
        }
    }

    /**
     * Filters a URL GET parameter with a specified filter
     *
     * This method is just an alias for {@link Pico::filterVariable()}, see
     * {@link Pico::filterVariable()} for a detailed description. It can be
     * used in Twig templates by calling the `url_param` function.
     *
     * @see    Pico::filterVariable()
     * @param  string                    $name    name of the URL GET parameter
     *     to filter
     * @param  int|string                $filter  the filter to apply
     * @param  mixed|array               $options either a associative options
     *     array to be used by the filter or a scalar default value
     * @param  int|string|int[]|string[] $flags   flags and flag strings to
     *     be used by the filter
     * @return mixed                              either the filtered data,
     *     FALSE if the filter fails, or NULL if the URL GET parameter doesn't
     *     exist and no default value is given
     */
    public function getUrlParameter($name, $filter = '', $options = null, $flags = null)
    {
        if ($this->disabledSilently) {
            return call_user_func_array(array($this->getPico(), 'getUrlParameter'), func_get_args());
        }

        $variable = (isset($_GET[$name]) && is_scalar($_GET[$name])) ? $_GET[$name] : null;
        return $this->filterVariable($variable, $filter, $options, $flags);
    }

    /**
     * Filters a HTTP POST parameter with a specified filter
     *
     * This method is just an alias for {@link Pico::filterVariable()}, see
     * {@link Pico::filterVariable()} for a detailed description. It can be
     * used in Twig templates by calling the `form_param` function.
     *
     * @see    Pico::filterVariable()
     * @param  string                    $name    name of the HTTP POST
     *     parameter to filter
     * @param  int|string                $filter  the filter to apply
     * @param  mixed|array               $options either a associative options
     *     array to be used by the filter or a scalar default value
     * @param  int|string|int[]|string[] $flags   flags and flag strings to
     *     be used by the filter
     * @return mixed                              either the filtered data,
     *     FALSE if the filter fails, or NULL if the HTTP POST parameter
     *     doesn't exist and no default value is given
     */
    public function getFormParameter($name, $filter = '', $options = null, $flags = null)
    {
        if ($this->disabledSilently) {
            return call_user_func_array(array($this->getPico(), 'getFormParameter'), func_get_args());
        }

        $variable = (isset($_POST[$name]) && is_scalar($_POST[$name])) ? $_POST[$name] : null;
        return $this->filterVariable($variable, $filter, $options, $flags);
    }

    /**
     * Filters a variable with a specified filter
     *
     * This method basically wraps around PHP's `filter_var()` function. It
     * filters data by either validating or sanitizing it. This is especially
     * useful when the data source contains unknown (or foreign) data, like
     * user supplied input. Validation is used to validate or check if the data
     * meets certain qualifications, but will not change the data itself.
     * Sanitization will sanitize the data, so it may alter it by removing
     * undesired characters. It doesn't actually validate the data! The
     * behaviour of most filters can optionally be tweaked by flags.
     *
     * Heads up! Input validation is hard! Always validate your input data the
     * most paranoid way you can imagine. Always prefer validation filters over
     * sanitization filters; be very careful with sanitization filters, you
     * might create cross-site scripting vulnerabilities!
     *
     * @see    https://secure.php.net/manual/en/function.filter-var.php
     *     PHP's `filter_var()` function
     * @see    https://secure.php.net/manual/en/filter.filters.validate.php
     *     Validate filters
     * @see    https://secure.php.net/manual/en/filter.filters.sanitize.php
     *     Sanitize filters
     * @param  mixed                     $variable value to filter
     * @param  int|string                $filter   ID (int) or name (string) of
     *     the filter to apply; if omitted, the method will return FALSE
     * @param  mixed|array               $options  either a associative array
     *     of options to be used by the filter (e.g. `array('default' => 42)`),
     *     or a scalar default value that will be returned when the passed
     *     value is NULL (optional)
     * @param  int|string|int[]|string[] $flags    either a bitwise disjunction
     *     of flags or a string with the significant part of a flag constant
     *     (the constant name is the result of "FILTER_FLAG_" and the given
     *     string in ASCII-only uppercase); you may also pass an array of flags
     *     and flag strings (optional)
     * @return mixed                               with a validation filter,
     *     the method either returns the validated value or, provided that the
     *     value wasn't valid, the given default value or FALSE; with a
     *     sanitization filter, the method returns the sanitized value; if no
     *     value (i.e. NULL) was given, the method always returns either the
     *     provided default value or NULL
     */
    protected function filterVariable($variable, $filter = '', $options = null, $flags = null)
    {
        $defaultValue = null;
        if (is_array($options)) {
            $defaultValue = isset($options['default']) ? $options['default'] : null;
        } elseif ($options !== null) {
            $defaultValue = $options;
            $options = array('default' => $defaultValue);
        }

        if ($variable === null) {
            return $defaultValue;
        }

        $filter = !empty($filter) ? (is_string($filter) ? filter_id($filter) : (int) $filter) : false;
        if (!$filter) {
            return false;
        }

        $filterOptions = array('options' => $options, 'flags' => 0);
        foreach ((array) $flags as $flag) {
            if (is_numeric($flag)) {
                $filterOptions['flags'] |= (int) $flag;
            } elseif (is_string($flag)) {
                $flag = strtoupper(preg_replace('/[^a-zA-Z0-9_]/', '', $flag));
                if (($flag === 'NULL_ON_FAILURE') && ($filter ===  FILTER_VALIDATE_BOOLEAN)) {
                    $filterOptions['flags'] |= FILTER_NULL_ON_FAILURE;
                } else {
                    $filterOptions['flags'] |= (int) constant('FILTER_FLAG_' . $flag);
                }
            }
        }

        return filter_var($variable, $filter, $filterOptions);
    }
}
