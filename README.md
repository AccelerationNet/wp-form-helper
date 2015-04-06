# WP-form-helper

WP-form-helper is a collection of functions / shortcodes that make
creating forms in wordpress content easier for me.  These are not a
fully formed package, but rather a collection of utilities that I have
been reusing for a couple years.

## Basics

Creates form components:
 * that render to html
 * can perform basic validation on themselves
 * labels
 * add programatic classes based on type and validity
 * persist their values automatically across form posts / fill from the
request based on name.

### Form controls

 * [input]
 * [password]
 * [checkbox]
 * [textarea]
 * [radio]
 * [bool_radio] - a pair of radio buttons makeing a yes/no selector
 * [option] - options for a select box

### Logic

 * [if test="beforeDate('1/1/2014')"]hidden stuff[/if]
 * CANNOT BE NESTED! if you need to nest it you will need to create [if2]
   style shortcodes (this is a wordpress limitation)
