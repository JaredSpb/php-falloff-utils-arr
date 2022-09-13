<?php
/**
* Arr is a class implementing ArrayAccess and other object-as-array mechanics. It also provides a number of usefull methods.

* @package Falloff\Utils\Arr
* @license MIT
* @version 1.0.1 (2022-09-13)
* @author Jared <jared@falloff.com>
*/

namespace Falloff\Utils;


/**
* Base class providing trivial ArrayAccess, Countable and IteratorAggregate implementation.
* 
* Using `ArrBase` by it's own does not make much sense. There are two differences from the built-in
* `\ArrayObject` class. First is that inner storage is marked as `protected` so that ArrBase can be extended.
* And the second is the `ArrBase::raw()` method that returns internal storage by reference. But using the last is
* NOT recommended.
*/
class ArrBase implements \ArrayAccess, \Countable, \IteratorAggregate {

	/**
	* @internal
	*/
	protected $storage = [];

	function __construct( array $array = [] ){
		$this->storage = $array;
	}

	/**
	* Returns an underlying storage by reference. Using this is NOT recommended.
	* 
	* @return array &$storage
	*/
	function &raw() : array {
		return $this->storage;
	}
	function getArrayCopy() : array {
		return $this->storage;
	}

	// ArrayAccess
	function offsetExists($offset) : bool { return array_key_exists($offset, $this->storage); }
	function offsetGet($offset) : mixed { return $this->storage[$offset]; }
	function offsetSet($offset, $value) : void { 
		if( is_null($offset) ) $offset = count( $this->storage );
		$this->storage[$offset] = $value; 
	}

	function offsetUnset($offset) : void { unset($this->storage[$offset]); }
	// Countable
	function count() : int { return count( $this->storage ); }

	// IteratorAggregate
	function getIterator() : \ArrayIterator { return new \ArrayIterator( $this->storage ); }

}


/**
* Provides extra methods on top of an \ArrayAccess basics.
*
* Base OOP-style usage:
* ```php
* use Falloff\Utils\Arr;
* $arr = new Arr([1,2,3]);
* ```
* When a static method returns an array, instance method returns a `new Arr` instance.
*/
class Arr extends ArrBase {

	/**
	* @internal
	*/
	private static function _merge($defaults, $params) : array {
		$params_keys = array_keys( $params );
		if( empty( $params ) )
			return $defaults;

		// True if params are passed as a numeric array: [ 'param1', 'param2', ... ]
		// False if params are passed as a hash array: [ 'key1' => 'param1', 'key2' => 'param2', ... ]
		$numeric_style = Arr::array_all( array_keys($params), 'numeric' );

		foreach( $defaults as $k => $default ){
			if( $numeric_style )
				$defaults[$k] = array_shift( $params );
			elseif( !empty( $params[ $k ] ) ){
				$defaults[$k] = $params[ $k ];
				unset( $params[$k] );
			}

			if( !count( $params ) )
				break;
		}
		return $defaults;
	}

	/**
	* @internal
	*/
	private static function _is_countable($what) : bool {
		if( is_array($what) ) 
			return true;
		if( is_object($what) and in_array('Countable', class_implements($what)) )
			return true;

		return false;

	}

	// ################################################################################
	// == Dupes buildin PHP functions ==
	// ################################################################################

	/**
	* Works pretty the same as a built-in PHP function:
	*
	* ```php
	* $array = new Arr([1,2]);
	* $array->shift(); // returns 1
	* // $array is now Arr([2])
	* ```
	*
	* @group Basic
	*/
	function shift() {
		return array_shift($this->storage);
	}
	/**
	* Works pretty the same as a built-in PHP function:
	*
	* ```php
	* $array = new Arr([1,2]);
	* $array->unshift(9); // returns 3
	* // $array is now Arr([9,1,2])
	* ```
	* @param mixed $value the value to add to array
	* 
	* @group Basic
	*/
	function unshift( $value ) : int {
		array_unshift($this->storage, $value);
		return count( $this->storage );
	}
	/**
	* Works pretty the same as a built-in PHP function:
	*
	* ```php
	* $array = new Arr([1,2]);
	* $array->pop(); // returns 2
	* // $array is now Arr([1])
	* ```
	*
	* @group Basic
	*/
	function pop(){
		return array_pop($this->storage);
	}
	/**
	* Works pretty the same as a built-in PHP function:
	*
	* ```php
	* $array = new Arr([1,2]);
	* $array->push(9); // returns 3
	* // $array is now Arr([1,2,9])
	* $array[] = 9; // Doing the same
	* ```
	*
	* @param mixed $value the value to add to array
	*
	* @group Basic
	*/
	function push($value) : int {
		array_push($this->storage, $value);
		return count( $this->storage );
	}

	/**
	* Works pretty the same as a built-in PHP function:
	*
	* ```php
	* $array = new Arr([1,2,3]);
	* $array->map( function( $el ){ return $el^2; } ); // returns new Arr([1,4,9])
	* ```
	*
	* @param mixed $fn callback to apply to each element
	*
	* @group Basic
	*/
	function map( callable $fn ) : Arr {
		return new Arr(array_map($fn, $this->storage));
	}

	/**
	* Works pretty the same as a built-in PHP function:
	*
	* ```php
	* $array = new Arr(['one' => 1, 'two' => 2, 'three' => 3]);
	* $array->values(); // returns new Arr([1,2,3])
	* ```
	*
	* @group Basic
	*/
	function values() : Arr {
		return new Arr(array_values($this->storage));
	}
	/**
	* Works pretty the same as a built-in PHP function:
	*
	* ```php
	* $array = new Arr(['one' => 1, 'two' => 2, 'three' => 3]);
	* $array->keys(); // returns new Arr(['one','two','three'])
	* ```
	*
	* @group Basic
	*/
	function keys() : Arr {
		return new Arr(array_keys($this->storage));
	}

	/**
	* Replacement for an `array_key_exists` built-in function
	*
	* ```php
	* $array = new Arr(['one' => 1, 'two' => 2, 'three' => 3]);
	* $array->hasKey('one'); // true
	* $array->hasKey('four'); // false
	* ```
	*
	* @group Basic
	* @group Checks
	*/
	function hasKey( $key ) : bool {
		return array_key_exists($key, $this->storage);
	}

	/**
	* Replacement for an `in_array` built-in function
	*
	* ```php
	* $array = new Arr(['one' => 1, 'two' => 2, 'three' => 3]);
	* $array->has(1); // true
	* $array->hasKey(4); // false
	* ```
	*
	* @group Basic
	* @group Checks
	*/
	function has( $value ) : bool {
		return in_array($value, $this->storage);
	}

	/**
	* Checks if Arr has data
	*
	* ```php
	* (new Arr())->isEmpty(); // true
	* (new Arr([]))->isEmpty(); // true
	* (new Arr([1]))->isEmpty(); // false
	* ```
	*
	* @group Checks
	*/
	function isEmpty() : bool {
		return empty($this->storage);
	}


	/**
	* Returns the first value, no matter if the array is a list or a hash.
	* ```php
	* (new Arr([1,2,3]))
	* 	->first(); // returns 1
	* 
	* (new Arr(['first' => 1, 'second' => 2, 'third' =>3]))
	* 	->first(); // returns 1
	*
	* Arr::array_first([1,2,3]); // returns 1
	* Arr::array_first(['first' => 1, 'second' => 2, 'third' =>3]); // returns 1
	* ```
	*
	* @throws ArrIsEmptyException when trying to fetch a value from an empty array
	*
	* @group Getters
	*/

	function first() {
		return self::array_first( $this->storage );
	}

	/**
	@see Arr::first()
	*/
	static function array_first( array $array ) {
		if( empty( $array ) )
			throw new ArrIsEmptyException( "Cannot get a first value in the empty array" );
		$keys = array_keys( $array );
		return $array[ $keys[0] ];
	}


	/**
	* Returns the last value, no matter if the array is a list or a hash.
	* ```php
	* (new Arr([1,2,3]))
	* 	->last(); // returns 3
	* 
	* (new Arr(['first' => 1, 'second' => 2, 'third' =>3]))
	* 	->last(); // returns 3
	*
	* Arr::array_last([1,2,3]); // returns 3
	* Arr::array_last(['first' => 1, 'second' => 2, 'third' =>3]); // returns 3
	* ```
	*
	* @throws ArrIsEmptyException when trying to fetch a value from an empty array
	*
	* @group Getters
	*/

	function last() {
		return self::array_last( $this->storage );
	}

	/**
	@see Arr::last()
	*/
	static function array_last( array $array ) {
		if( empty( $array ) )
			throw new ArrIsEmptyException( "Cannot get a last value in the empty array" );
		$keys = array_keys( $array );
		return $array[ $keys[ count( $keys ) - 1] ];
	}


	/**
	* Checks if all array values satisfy a crtiteria provided.
	* 
	* $criteria is a callable or either some predefined criteria.
	* Predefined criterias include:
	*	- 'numeric' - check if all values are numeric
	*	- 'even' - checks if all values are numeric AND are even
	*	- 'odd' - checks if all values are numeric AND are odd
	*
	* ```php
	* (new Arr([1,2,3]))->all(function( $value ){
	* 	return $value % 2 == 0;
	* }); # false
	* ```
	* @param mixed $criteria predefined string or a callable to check array elements
	* @group Checks
	*
	*/
	function all( $criteria ) : bool {
		return self::array_all( $this->storage, $criteria );
	}
	/**
	* @see Arr::all()
	*/
	static function array_all( array $array, $criteria ) : bool {

		if( is_callable( $criteria ) ){
			foreach( $array as $entry ){
				if( ! $criteria( $entry ) ) 
					return false;
			}
		}
		elseif( $criteria == 'numeric' ){
			foreach( $array as $entry ){
				if( ! is_numeric( $entry ) ) 
					return false;
			}
		}
		elseif( $criteria == 'odd' || $criteria == 'even' ){
			foreach( $array as $entry ){
				if( 
					! is_numeric( $entry )
					or $entry % 2 != ($criteria == 'odd' ? 1 : 0)
				) {
					return false;
				}
			}
		}

		return true;
	}


	/**
	* Returns a random value from the array.
	*
	* ```php
	* (new Arr([1,2,3]))->randomValue(); // one of values selected by rand()
	* Arr::array_random_value([1,2,3]);  // one of values selected by rand()
	* ```
	* 
	* @throws ArrIsEmptyException when trying to fetch a value from an empty array
	*
	* @group Getters
	*/
	function randomValue() {
		return self::array_random_value( $this->storage );
	}
	static function array_random_value( array $arr ) {
		if( empty( $arr ) )
			throw new ArrIsEmptyException( "Cannot find a random value in the empty array" );

		$keys = array_keys($arr);
		return $arr[ $keys[ rand(0,count($keys) - 1) ] ];
	}

	/**
	* Shorthand to the CSS separator strings for use with the Arr::join() and Arr::array_join() methods
	* @see Arr::join()
	*/
	const JOIN_CSS = [ 
		'elements_glue' => ";\n", 
		'key_value_glue' => ': ', 
		'value_wrapper' => '',
		'value_glue' => ' ',
		'value_escape' => '' ,
	];

	/**
	* Shorthand to the HTML separator strings for use with the Arr::join() and Arr::array_join() methods
	* @see Arr::join()
	*/
	const JOIN_HTML = [ 
		'elements_glue' => ' ', 
		'key_value_glue' => '=', 
		'value_wrapper' => '"',
		'value_glue' => ' ',
		'value_escape' => '&quot;',
	];

	/**
	* Joins the array into a string using provided strings as separators.
	* Accepts an array of strings that are used as key-value separators, 
	* key-value groups separators, etc. 
	*
	* Predefined Arr::JOIN_CSS and Arr::JOIN_HTML constants contain strings required for 
	* producing CSS and HTML strings respectively.
	*
	* Examples:
	*
	* ```php
	* (new Arr([
	* 		'transform' => ['scale(1.5)', 'rotate(90deg)'], 
	* 		'background-image' => 'url("/path/to/img")'
	* 	]))->join( Arr::JOIN_CSS );  // returns the following:
	*  	                              # transform: scale(1.5) rotate(90deg);
	*  	                              # background-image: url("/path/to/img")
	* 
	* Arr::array_join(
	* 	[
	* 		'transform' => ['scale(1.5)','rotate(90deg)'], 
	* 		'background-image' => 'url("/path/to/img")'
	* 	], 
	* 	Arr::JOIN_CSS
	* ); // function style, returns the same as the above example
	* ```
	* 
	* Or with HTML:
	* ```php
	* (new Arr(['class' => ['someclass','other"class'], 'id' => 'uniq']) )
	* 	->join( Arr::JOIN_HTML ); // returns
	* 	                          # 'class="someclass other&quot;class" id="uniq"'
	* Arr::array_join(
	* 	['class' => ['someclass','other"class'], 'id' => 'uniq'], 
	* 	Arr::JOIN_HTML
	* ); // returns the same as above exmple
	* ```
	* 
	* Using custom separators:
	* ```php
	* (new Arr(['class' => ['someclass','other"class'], 'id' => 'uniq']))
	*	->join([
	* 	'elements_glue' => ' ',     // key-value groups separator
	* 	'key_value_glue' => '=',    // key-value separator
	* 	'value_wrapper' => '"',     // value wrapper
	* 	'value_glue' => ' ',        // joins the value with this (if value is an array)
	* 	'value_escape' => '&quot;', // replacement to the value-wrapper symbol met in the value
	* ]); // Produces same output as the example with JOIN_HTML above
	*
	* @param array $args array of strings to build the result; structure is:
	* ``` php
	* [
	* 	'elements_glue' => ' ',     // key-value groups separator
	* 	'key_value_glue' => '=',    // key-value separator
	* 	'value_wrapper' => '"',     // value wrapper
	* 	'value_glue' => ' ',        // joins the value with this (if value is an array)
	* 	'value_escape' => '&quot;', // replacement to the value-wrapper symbol met in the value
	* ]
	* ```
	* 
	* @group Reducers
	*/

	function join( array $args ) : string {
		return self::array_join( $this->storage, $args );
	}
	/**
	* @see Arr::join()
	*/
	static function array_join( array $array, array $args ) : string {
		$defaults = [
			'elements_glue' => "", 
			'key_value_glue' => '', 
			'value_wrapper' => '',
			'value_glue' => '',
			'value_escape' => ''
		];
		$params = self::_merge( $defaults, $args );

		return implode(
			$params['elements_glue'], 
			array_map(
				function($key) use ($array, $params){
					return implode(
						"", 
						[
							$key, 
							$params['key_value_glue'],
							$params['value_wrapper'],
							str_replace(
								$params['value_wrapper'], 
								($params['value_escape']?$params['value_escape']:$params['value_wrapper']), 
								( 
									is_array($array[$key] )
										? implode($params['value_glue'] ?? '' , $array[$key])
										: $array[$key]
								)
							),
							$params['value_wrapper'],
						]
					);
				}, 
				array_keys($array)
			)
		);
	}

	/**
	* Returns an array or an Arr instance with desired keys only.
	* Original order is preserved.
	* Keys might be just enlisted as arguments or provided as a separate array.
	* 
	* 
	* ```php
	* (new Arr([
	* 	'key1' => 'value1', 
	* 	'key2' => 'value2',
	*	'key3' => 'value3',
	* ]))->kslice('key3','key1'); // returns <<<
	*                              # new Arr([
	*                              # 	'key1' => 'value1',
	*                              # 	'key3' => 'value3'
	*                              # ])
	*                             // >>>
	* Arr::array_kslice(
	* 	[
	* 		'key1' => 'value1', 
	* 		'key2' => 'value2',
	* 		'key3' => 'value3',
	* 	],
	* 	[ 'key3', 'key1' ]
	* ); // returns plain array with the same data as in the previous example
	*
	* @group Reducers
	* @group Subarrays
	*/
	function kslice( ...$keys ) {
		return new Arr(self::array_kslice( $this->storage, ...$keys ));
	}
	/**
	* @see Arr::kslice()
	*/
	static function array_kslice( $array, ...$keys ){

		if( is_array( $keys[0] ) and count($keys) == 1 )
			$keys = $keys[0];

		$out = [];
		foreach( $array as $k => $v )
			if( in_array($k, $keys) ) 
				$out[ $k ] = $v;

		return $out;
	}

	// ---------------------------------------------
	// In or out subarrays functions
	// ---------------------------------------------

	/**
	* Groups the key-value pairs. 
	* Grouping is based on result the provided callback execution.
	*
	* ```php
	* $arr = [
	*	'group1.field1' => 'value1.1', 
	*	'group1.field2' => 'value1.2', 
	*	'group2.field1' => 'value2.1', 
	* ];
	* $groupper = function($value, $key){
	* 	[$group_name, $entry_key] = explode(',', $key);
	* 	return [ $group_name, $entry_key, $value ]; # unchanged $entry_key/$value may be ommited
	* };
	*
	* (new Arr($arr))->group( $groupper ); // results in <<<
	* 	# new Arr([
	* 	#	group1 => [
	* 	#		'field1' => 'value1.1',
	* 	#		'field2' => 'value1.2',
	* 	#	],
	* 	#	group2 => [
	* 	#		'field1' => 'value2.1'
	* 	#	]
	* 	#]);
	* 	// <<<
	*
	* // Same with a static method:
	* Arr::array_group($arr, $groupper);
	* ```
	*
	* @param callable $fn 
	* Takes $value and $key as arguments. 
	*
	* Must return an array with the following elements:	* 
	* - 0 - group name, mandatory
	* - 1 - group element key. If omited, the original key will be used. Optional.
	* - 2 - group element value If omited, the original value will be used. Optional.
	* Might return null instead (what means "throw this element away").
	*
	* Or a string representing group name (key and value will be preserved). This is the same as
	* above with one-element array.
	*
	* @group Reducers
	* @group Subarrays
	*/

	function group( callable $fn) : Arr {
		return new Arr( self::array_group( $this->storage, $fn ) );
	}
	/**
	* @see Arr::group()
	*/
	static function array_group( array $array, callable $fn ) : array {

		$groups = [];
		foreach( $array as $k => $v ){
			$rv = $fn( $v, $k );
			if( is_null( $rv ) )
				continue;

			$field_name = null;
			if( is_array( $rv ) ){
				$group_name = array_shift( $rv );

				$field_name = (
					count( $rv )
					? array_shift( $rv )
					: $k
				);

				if( count( $rv ) ) 
					$v = array_shift( $rv );
			} else {
				$group_name = $rv;
				$field_name = $k;
			}

			if( empty( $groups[$group_name] ) )
				$groups[$group_name] = [];

			$groups[$group_name][ $field_name ] = $v;
		}

		return $groups;
	}


	/**
	* @internal
	*/
	static function array_key_exists( $key, $array_object ){
		if( is_a($array_object, __CLASS__) )
			return $array_object->hasKey( $key );
		return array_key_exists($key, $array_object);

	}


	/**
	* Removes provided prefix from the array keys
	*
	* ```php
	* $array = [
	* 	'prefix->key' => 'value1' ,
	* 	'prefix->key2' => 'value2' ,
	* 	'NONprefix->key' => 'value3',
	* ];
	*   
	* (new Arr( $array ))->deprefixKeys('prefix->'); 
	* 	// results in <<<
	* 	# new Arr([
	* 	#	'key' => 'value1',
	* 	#	'key2' => 'value2'
	* 	# ]);
	* 	// >>>
	*
	* Arr::array_deprefix_keys( $array, 'prefix->', true );
	* 	// results in <<<
	* 	# [
	* 	#	'key' => 'value1',
	* 	#	'key2' => 'value2'
	* 	#	'NONprefix->key' => 'value3'
	* 	# ]
	* 	// >>>
	* ```
	* 
	* @param bool $prefix the prefix string to be removed from the keys
	* @param bool $preserve_nonprefixed whenever to throw away keys that are not prefixed with a provided string.
	*/
	function deprefixKeys( string $prefix, bool $preserve_nonprefixed = false ) : Arr {
		return new Arr( self::array_deprefix_keys( $this->storage, $prefix, $preserve_nonprefixed ) );
	}

	/**
	* @see Arr::deprefixKeys()
	*/
	static function array_deprefix_keys( array $arr, string $prefix, bool $preserve_nonprefixed = false) : array {
		$prefix_length = strlen($prefix);
		$out = [];
		foreach($arr as $key => $value){
			if( strpos($key, $prefix) === 0 )
				$out[ substr($key, $prefix_length) ] = $value;
			elseif( $preserve_nonprefixed )
				$out[ $key ] = $value;
		}
		return $out;
	}


	/**
	* Looks for the intersection of two overlapping arrays.
	* Returns the starting and the ending index of the overlap
	* relative to the first array provided
	* If no overlap found returns null;
	* 
	* ```php
	* $a1 = [0,1,2,3,5,6];
	* $a2 =       [3,5,6,7,8,9];
	* (new Arr( $a1 ))->intersectOffset( $a2 );
	* (new Arr( $a1 ))->intersectOffset( new Arr( $a2 ) );
	* 	// Both result in: <<<
	* 	# new Arr([
	* 	# 	'start' => 3,
	* 	# 	'end' => 6,
	* 	# ]);
	* 	// >>>
	* 
	* // using static method:
	* Arr::array_intersect_offset( $a1, $a2 ); // [ start => 3, end => 6 ];
	* 
	* $a1 = ['zero','one','two'];
	* $a2 = ['will','not','intersect'];
	* array_intersect_offset( $a1, $a2 ); // null
	* ```
	*
	* @param array|Arr $array the array to look intersection with
	*
	* @group Misc
	*/

	function intersectionOffset( $array ) : Arr {
		if( is_object($array) ){
			if( !method_exists($array, 'getArrayCopy') )
				throw new \Exception( "Argument object MUST implement `getArrayCopy` method" );

			$array = $array->getArrayCopy();
		}
			

		$rv = self::array_intersection_offset( 
			$this->storage, 
			$array 
		);
		if( is_array( $rv ) )
			return new Arr( $rv );
		return $rv;
	}

	/**
	* @see Arr::intersectOffset()
	*/
	static function array_intersection_offset( array $arr1, array $arr2 ) : ?array {
		$start = null;
		$end = 0;
		$length = 0;

		foreach (range(count($arr1)-1, 0) as $offset) {

			if($arr2[0] == $arr1[$offset]){

				$start = $offset;
				$length = 0;

				while( ++$length ){

					// reached the Arr1 end, we'r done
					if( ! self::array_key_exists( $offset + $length, $arr1 ) )
						break 2;

					// reached the Arr2 end, we'r done
					if( ! self::array_key_exists( $length, $arr2 ) )
						break 2;

					// Nope, not a real intersect
					if( $arr1[ $offset + $length ] != $arr2[ $length ]  ){
						$start = null;
						continue 2;
					}

				}

			}
		}

		return (
			!is_null($start)
			? [ 'start' => $start, 'end' => $start + $length ]
			: null
		);

	}

	/**
		==========================================================================================
			Array of arrays functions
		==========================================================================================
	*/

	/**
	* Analizes the plain adjacent tree array-of-arrays
	* and produces recursive structure of elements.
	* Returns an array of the following keys:
	* - all - contains all the elements indexed by their id
	* - roots - contains elements who's parent_id is empty
	* - structure - plain `id => parent_id` array
	* - orphans - contains elements who's parent was not found
	*
	* Every non-childless element will have a `_children` (by default) key, containing
	* its children. These children are indexed via their `$params['alias_key']` value. 
	* If `$params['alias_key']` was set to null, children will be indexed by a plain int key.
	* If a particular element does not have a `$params['alias_key']`, it will be indexed by it's id.
	*  
	* ```php
	* $raw = [
	* 	['id' => 1, 'parent_id' => null, 'name' => 'root1' ],
	* 	['id' => 11, 'parent_id' => 1, 'name' => 'child11' ],
	* 	['id' => 2, 'parent_id' => null, 'name' => 'root2' ],
	* 	['id' => 22, 'parent_id' => 2, 'name' => 'child22' ],
	* 	['id' => 222, 'parent_id' => 22, 'name' => 'child222' ],
	* 	['id' => 33, 'parent_id' => 3, 'name' => 'orphan' ],
	* 	['id' => 333, 'parent_id' => 33, 'name' => 'orphan_child' ]
	* ];
	*
	* $result = Arr::array_as_tree( $raw );
	* // result is:
	* $result = [
	*  	'roots' => [
	* 		'root1' => [
	* 			'id' => 1, 
	* 			'parent_id' => null, 
	* 			'name' => 'root1',
	* 			'_children' => [
	* 				'child1' => ['id' => 11, 'parent_id' => 1, 'name' => 'child11' ]
	* 			]
	* 		],
	* 		'root2' => [
	* 			'id' => 2, 
	* 			'parent_id' => null, 
	* 			'name' => 'root2',
	* 			'_children' => [
	* 				'child2' => [
	* 					'id' => 22, 
	* 					'parent_id' => 1, 
	* 					'name' => 'child2' ,
	* 					'_children' => [
	* 						'child222' => ['id' => 222, 'parent_id' => 22, 'name' => 'child222' ]
	* 					]
	* 				],
	* 			]
	* 		],
	* 	],
	*  	'orphans' => [
	* 		'orphan' => [
	* 			'id' => 33, 
	* 			'parent_id' => 3, 
	* 			'name' => 'orphan',
	* 			'_children' => [
	* 				'child1' => ['id' => 333, 'parent_id' => 33, 'name' => 'orphan_child' ]
	* 			]
	* 		],
	* 	],
	*  	'structure' => [
	* 		1 => null,
	* 		11 => 1,
	* 		2 => null,
	* 		22 => 2,
	* 		222 => 22,
	* 		33 => 3,
	* 		333 => 33,
	* 	],
	*  	'all' => [
	* 		# Contains list of all elemets from the 
	* 		# original array along with their _children.
	* 		# Orphans included.
	* 		# `all` is indexed by element id.
	* 	],
	* ]
	* ```
	* @param string $params an array defining element's structure, might have following keys
	* - `id_key => 'id'` string, the key holding element's id
	* - `pid_key => 'parent_id'` string, the key holding element's parent id
	* - `alias => 'name'` string or null, the key holding an alias that will be used in a children array
	* - `children_key => '_children'` string, the key holding element's id
	*
	* @param string $collection_class will be used as a container class for all the elements. 
	* Arr instances will use the Arr by default.
	*
	* @group Subarrays
	*/

	function asTree( 
			array $params = [],
			string $collection_class = null			
		) : Arr {
		return self::array_as_tree(
			$this->storage,
			$params,
			__CLASS__
		);
	}

	/**
	* @see Arr::asTree()
	*/
	static function array_as_tree(
		array $array, 
		array $params = [],
		string $collection_class = null
	){
		$defaults = [
			'id_key' => 'id', 
			'pid_key' => 'parent_id', 
			'alias_key' => 'name', 
			'children_key' => '_children',
		];
		foreach( $defaults as $param => $default_value ){
			if( !array_key_exists($param, $params) )
				$params[ $param ] = $default_value;
		}
		extract( $params );

		$results = [];
		$helper_vars = ['all', 'roots', 'orphans', 'structure'];
		foreach( $helper_vars as $k )
			$results[ $k ] = ( $collection_class ? new $collection_class([]) : [] );

		extract($results);

		// Populate all's and structure
		foreach( $array as $k => $value ){
			$id = (
				$id_key
				? $value[ $id_key ]
				: $k
			);
			$parent_id = empty($value[ $pid_key ]) ? null : $value[ $pid_key ];

			$all[ $id ] = $collection_class ? new $collection_class($value) : $value;
			$structure[ $id ] = $parent_id;
		}


		// Fill in the real tree
		foreach( $structure as $child_id => $parent_id ){

			// This is the root element
			if( empty( $parent_id ) ){
				if( empty( $alias_key ) ){

					if( empty( $collection_class ) )
						$roots[] = &$all[$child_id];
					else
						$roots[] = $all[$child_id];

				} else{
					$alias = 
						self::array_key_exists($alias_key, $all[ $child_id ]) 
						? $all[ $child_id ][ $alias_key ] 
						: $child_id;

					// With a collection class we'r getting the 
					// desired element as an object reference
					if( empty( $collection_class ) )
						$roots[ $alias ] = &$all[$child_id];
					else
						$roots[ $alias ] = $all[$child_id];

				}
			}

			// This is the orphan
			elseif( empty( $all[ $parent_id ] ) ){
				if( empty( $alias_key ) )

					if( empty( $collection_class ) )
						$orphans[] = &$all[$child_id];
					else
						$orphans[] = $all[$child_id];

				else{
					$alias = 
						self::array_key_exists($alias_key, $all[ $child_id ]) 
						? $all[ $child_id ][ $alias_key ] 
						: $child_id;


					if( empty( $collection_class ) )
						$orphans[ $alias ] = &$all[$child_id];
					else
						$orphans[ $alias ] = $all[$child_id];

				}
			}

			// This is a regular entry
			else{
				if( empty( $all[ $parent_id ][ $children_key ] ) )
					$all[ $parent_id ][ $children_key ] = $collection_class ? new $collection_class([]) : [];

				if( empty( $alias_key ) ){
					if( empty( $collection_class ) )
						$all[ $parent_id ][ $children_key ][] = &$all[ $child_id ];
					else
						$all[ $parent_id ][ $children_key ][] = $all[ $child_id ];

				} else{
					$alias = 
						self::array_key_exists($alias_key, $all[ $child_id ]) 
						? $all[ $child_id ][ $alias_key ] 
						: $child_id;

					if( !empty($all[ $parent_id ][ $children_key ][ $alias ]) ){
						throw new \Exception("Non-unique alias `$alias` for a child. Child is `$child_id`, parent is $parent_id");
					}

					if( empty( $collection_class ) )
						$all[ $parent_id ][ $children_key ][ $alias ] = &$all[$child_id];
					else 
						$all[ $parent_id ][ $children_key ][ $alias ] = $all[$child_id];

				}			
			}
		}


		$rv = compact($helper_vars);
		return (
			$collection_class
			? new $collection_class( $rv )
			: $rv
		);
	}

	/**
	* Returns the shortest subarray.
	* ```php
	* $raw = [
	* 	[1,2,3,4],
	* 	[1,2],
	* 	[1,2,4],
	* 	[3,4],
	* ];
	* 
	* (new Arr( $raw ))->shortest(); // new Arr([1,2]);
	* Arr::array_shortest( $raw ); // [1,2]
	* (new Arr( $raw ))->shortest( false ); // new Arr([3,4]);
	* Arr::array_shortest( $raw, false ); // [3,4]
	* ```
	* 
	* @param bool $first_match whenever to return the earliest met shortest subarray
	* 
	* @group Reducers
	*/
	function shortest( $first_match = true ){
		$rv = self::array_shortest( $this->storage, $first_match );
		return !is_object( $rv ) ? new Arr( $rv ) : $rv;
	}

	/**
	* @see Arr::shortest()
	*/
	static function array_shortest( array $arr, $first_match = true ){

		return array_reduce($arr, function($current, $new) use( $first_match ) {

			// Compare args
			if(
				self::_is_countable( $current )
				and 
				self::_is_countable( $new )
			){

				if( 
					count($current) == count($new) 
					and $first_match 
				) {
					return $current;
				}

				if( count($current) >= count($new) ){
					return $new;
				}

				return $current;
			}

			// One if the args cannot be compared, check if $new is 
			// something that can be counted at all and just 
			// spew current if not
			if(! self::_is_countable( $new ) ) 
				return $current;
			
			// $new is countable and current is not. 
			return $new;

		}, null);
	}


	/**
	* Extracts a particular value from subarrays and returns
	* and array built from those values.
	*
	* ```php
	* $raw = [
	* 	[id => 1, name => 'name1'],
	* 	[id => 2, name => 'name2'],
	* ];
	*
	* (new Arr( $raw ))->extractValues( 'name' ); // new Arr( [ 'name1', 'name2' ] );
	* Arr::array_extract_values( $raw, 'name' ); // [ 'name1', 'name2' ];
	* ```
	*
	* @param $key mixed
	*
	* @group Reducers
	* @group Subarrays
	*/

	function extractValues( string $key ){
		return new Arr( self::array_extract_values( $this->storage, $key ) );
	}

	/**
	* @see Arr::extractValues();
	*/
	static function array_extract_values(array $arr, string $key){

		$rv = [];
		foreach ($arr as $sarr) {
			if(is_array($sarr) and array_key_exists($key, $sarr) ){
				$rv[] = $sarr[$key];
			}
		}

		return $rv;
	}

	/*
	* Reindex array using inner-array value.
	* OOP version will not turn plain inner arrays into
	* Arr instances.
	*
	* ```php
	* $raw = [
	* 	'somekey' => ['key_field' => 'keyOne'],
	* 	'otherkey' => ['key_field' => 'keyTwo'],
	* ];
	*
	* (new Arr( $raw ))->reindex( 'key_field' ); // returns <<<
	* # new Arr([
	* #	'keyOne' => ['key_field' => 'keyOne'],
	* #	'keyTwo' => ['key_field' => 'keyTwo'],
	* # ]);
	* // >>>
	*
	* Arr::array_reindex( $raw, 'key_field' ); // returns <<<
	* # [
	* #	'keyOne' => ['key_field' => 'keyOne'],
	* #	'keyTwo' => ['key_field' => 'keyTwo'],
	* # ]; 
	* // >>>
	* ```
	* 
	* @param mixed $key
	*
	* @group Misc
	*/
	function reindex( string $key ){
		return new Arr( self::array_reindex( $this->storage, $key ) );
	}

	/**
	* @see Arr::reindex()
	*/
	static function array_reindex( array $array, string $key ){
		$out = [];
		foreach( $array as $k => $v ){
			if( is_object( $v ) )
				$out[ $v->$key ] = $v;
			elseif( is_array( $v ) )
				$out[ $v[$key] ] = $v;
		}
		return $out;
	}

}



/**
* Arr exceptions base
*/
class ArrException extends \Exception{}
/**
* Exceptions to be thrown on meaningless operations on an empty array
*/
class ArrIsEmptyException extends ArrException{}
