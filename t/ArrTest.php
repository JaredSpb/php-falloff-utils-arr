<?php 
use PHPUnit\Framework\TestCase;
use \Falloff\Utils\Arr;

final class ArrTest extends TestCase
{
    public function testObjConstruction(): void
    {
        $arr = new Arr();
        $this->assertInstanceOf( Arr::class, $arr);

        $arr = new Arr([]);
        $this->assertInstanceOf(Arr::class,$arr);

        $arr = new Arr([0]);
        $this->assertInstanceOf(Arr::class,$arr);

        $this->expectException(TypeError::class);
        $arr = new Arr('not-an-array');
        $this->assertInstanceOf(Arr::class,$arr);
    }

    public function testDataExtraction(): void
    {
        $arr = new Arr([0]);
        $this->assertEquals( $arr->getArrayCopy(), [0]);

        $copy = &$arr->raw();
        $copy[] = 1;
        $this->assertEquals( $arr->getArrayCopy(), [0,1]);
    }

    public function testBaseChanges(): void
    {
        $arr = new Arr([0,1]);
        $this->assertEquals( $arr->push('zero'), 3);
        $this->assertEquals( $arr->pop(), 'zero');
        $this->assertEquals( count($arr), 2);

        $this->assertEquals( $arr->unshift('zero'), 3);
        $this->assertEquals( $arr->shift(), 'zero');
        $this->assertEquals( count($arr), 2);
    }

    public function testKeysValues(): void
    {
        $arr = new Arr(['zero' => 0, 'one' => 1]);

        $keys = $arr->keys();
        $this->assertInstanceOf( Arr::class, $keys);
        $this->assertEquals( $keys->getArrayCopy(), ['zero','one']);

        $values = $arr->values();
        $this->assertInstanceOf( Arr::class, $values);
        $this->assertEquals( $values->getArrayCopy(), [0,1]);

    }

    public function testMap(): void
    {
        $arr = new Arr(['zero' => 0, 'one' => 1, 'two' => 2]);
        $mapped_arr = $arr->map(function( $el ){
            return $el * $el;
        });

        $this->assertEquals( $mapped_arr->getArrayCopy(), [
            'zero' => 0,
            'one' => 1,
            'two' => 4
        ]);
    }

    public function testFirst(): void
    {
        $raw = ['zero' => 0, 'one' => 1, 'two' => 2];
        $arr = new Arr( $raw );

        $this->assertEquals( $arr->first(), 0 );
        $this->assertEquals( Arr::array_first($raw), 0 );
    }

    public function testAll(): void
    {
        $pass_test_fn = function( $el ){
            return $el % 2 == 0;
        };
        $fail_test_fn = function( $el ){
            return in_array($el, [2,4]);
        };

        $raw = ['two' => 2, 'four' => 4, 'six' => 6];
        $arr = new Arr( $raw );

        $this->assertEquals( $arr->all($pass_test_fn), true );
        $this->assertEquals( $arr->all($fail_test_fn), false );

        $this->assertEquals( Arr::array_all($raw, $pass_test_fn), true );
        $this->assertEquals( Arr::array_all($raw, $fail_test_fn), false );

    }

    public function testRandomValue(): void
    {
        $raw = ['two' => 2, 'four' => 4, 'six' => 6, 'eight' => 8];
        $arr = new Arr( $raw );

        $this->assertEquals( 
            in_array( $arr->randomValue() , array_values($raw)),
            true 
        );

        $this->assertEquals( 
            in_array( Arr::array_random_value( $raw ) , array_values($raw)),
            true 
        );

    }

    public function testJoin(): void
    {
        $raw = ['attr1' => 'value`1', 'attr2' => 'value"2', 'arr-attr' => ['arr-val"1', 'arr-val`2'], 'attr-last' => 'value-last'];
        $arr = new Arr( $raw );

        $this->assertEquals( 
            $arr->join( Arr::JOIN_HTML ),
            'attr1="value`1" attr2="value&quot;2" arr-attr="arr-val&quot;1 arr-val`2" attr-last="value-last"'
        );
        $this->assertEquals( 
            Arr::array_join($raw, Arr::JOIN_HTML ),
            'attr1="value`1" attr2="value&quot;2" arr-attr="arr-val&quot;1 arr-val`2" attr-last="value-last"'
        );

        $this->assertEquals( 
            $arr->join( Arr::JOIN_CSS ),
            "attr1: value`1;\nattr2: value\"2;\narr-attr: arr-val\"1 arr-val`2;\nattr-last: value-last"
        );
        $this->assertEquals( 
            Arr::array_join($raw, Arr::JOIN_CSS ),
            "attr1: value`1;\nattr2: value\"2;\narr-attr: arr-val\"1 arr-val`2;\nattr-last: value-last"
        );

        $custom_join_rules = [
            'elements_glue' => '//', 
            'key_value_glue' => ':=', 
            'value_wrapper' => '`',
            'value_glue' => ',',
            'value_escape' => '\\`',
        ];

        $this->assertEquals( 
            $arr->join( $custom_join_rules ),
            "attr1:=`value\\`1`//attr2:=`value\"2`//arr-attr:=`arr-val\"1,arr-val\\`2`//attr-last:=`value-last`"
        );


    }

    public function testKeySlice(): void
    {
        $raw = ['zero'=> 0, 'one' => 1, 'two' => 2, 'three' => 3, 'four' => 4];
        $arr = new Arr( $raw );


        $this->assertEquals( 
            Arr::array_kslice($raw, 'one', 'three', 'two'), 
            ['one' => 1, 'two' => 2, 'three' => 3] 
        );

        $this->assertEquals( 
            Arr::array_kslice($raw, ['one', 'three', 'two']), 
            ['one' => 1, 'two' => 2, 'three' => 3] 
        );

        $this->assertEquals( 
            $arr->kslice( 'one', 'three', 'two' )->getArrayCopy(), 
            ['one' => 1, 'two' => 2, 'three' => 3] 
        );

        $this->assertEquals( 
            $arr->kslice(['one', 'three', 'two'])->getArrayCopy(), 
            ['one' => 1, 'two' => 2, 'three' => 3] 
        );

    }

    public function testGroup(): void
    {
        $raw = ['zero'=> 0, 'one' => 1, 'two' => 2, 'three' => 3, 'four' => 4];
        $arr = new Arr( $raw );

        $result_array = [
            'even' => [
                'zero' => 0,
                'two' => 4,
                'four' => 16
            ],
            'odd' => [
                'one' => 1,
                'three' => 9
            ]
        ];


        $this->assertEquals( 
            Arr::array_group($raw, function( $value, $key ){
                return [
                    $value % 2 == 0 ? 'even' : 'odd',
                    $key,
                    $value * $value
                ];
            }), 
            $result_array
        );
        $this->assertEquals( 
            $arr->group(function( $value, $key ){
                return [
                    $value % 2 == 0 ? 'even' : 'odd',
                    $key,
                    $value * $value
                ];
            })->getArrayCopy(), 
            $result_array
        );

        
    }

    public function testToTreeStandard(): void
    {
        $raw = [
            // regular tree
            ['id' => 1, 'parent_id' => null, 'name' => 'root1'],
            ['id' => 2, 'parent_id' => null, 'name' => 'root2'],
            ['id' => 11, 'parent_id' => 1, 'name' => 'child11'],
            ['id' => 111, 'parent_id' => 11, 'name' => 'child111'],
            ['id' => 22, 'parent_id' => 2, 'name' => 'child22'],
            // orphan
            ['id' => 3, 'parent_id' => 333, 'name' => 'orphan'],
        ];

        $cmp_structure = [
            1 => null,
            2 => null,
            11 => 1,
            111 => 11,
            22 => 2,
            3 => 333
        ];
        $cmp = [
            'aliased' => [
                'orphans' => [ 'orphan' => ['id' => 3, 'parent_id' => 333, 'name' => 'orphan']],
                'keys' => ['all','roots','orphans','structure'],
                'first_root' => [
                    'id' => 1, 
                    'parent_id' => null, 
                    'name' => 'root1',
                    '_children' => [
                        'child11' => [
                            'id' => 11, 
                            'parent_id' => 1, 
                            'name' => 'child11',
                            '_children' => [
                                'child111' => ['id' => 111, 'parent_id' => 11, 'name' => 'child111'],
                            ]            
                        ]
                    ]
                ]
            ],
            'nonaliased' => [
                'orphans' => [ 0 => ['id' => 3, 'parent_id' => 333, 'name' => 'orphan']],
                'keys' => ['all','roots','orphans','structure'],
                'first_root' => [
                    'id' => 1, 
                    'parent_id' => null, 
                    'name' => 'root1',
                    '_children' => [
                        0 => [
                            'id' => 11, 
                            'parent_id' => 1, 
                            'name' => 'child11',
                            '_children' => [
                                0 => ['id' => 111, 'parent_id' => 11, 'name' => 'child111'],
                            ]            
                        ]
                    ]
                ]
            ]
        ];

        // With aliases
        $tree = Arr::array_as_tree( $raw );

        $this->assertEquals( array_keys( $tree ), $cmp[ 'aliased' ]['keys'] );
        $this->assertEquals( $tree['orphans'], $cmp[ 'aliased' ]['orphans'] );
        $this->assertEquals( array_key_exists( 'root1', $tree['roots'] ), true );
        $this->assertEquals( $tree['roots'][ 'root1' ], $cmp[ 'aliased' ]['first_root'] );
        $this->assertEquals( $tree['structure'], $cmp_structure );

        // With id's
        $tree = Arr::array_as_tree( $raw, 'id', 'parent_id', null );

        $this->assertEquals( array_keys( $tree ), $cmp[ 'nonaliased' ]['keys'] );
        $this->assertEquals( $tree['orphans'], $cmp[ 'nonaliased' ]['orphans'] );
        $this->assertEquals( array_key_exists( 0, $tree['roots'] ), true );
        $this->assertEquals( $tree['roots'][ 0 ], $cmp[ 'nonaliased' ]['first_root'] );


        // Using as method, also non-standard keys here
        $raw = [
            // regular tree
            ['ID' => 1, 'PID' => null, 'alias' => 'root1'],
            ['ID' => 2, 'PID' => null, 'alias' => 'root2'],
            ['ID' => 11, 'PID' => 1, 'alias' => 'child11'],
            ['ID' => 111, 'PID' => 11, 'alias' => 'child111'],
            ['ID' => 22, 'PID' => 2, 'alias' => 'child22'],
            // orphan
            ['ID' => 3, 'PID' => 333, 'alias' => 'orphan'],

        ];
        $cmp_structure = new Arr($cmp_structure);
        $cmp = [
            'orphans' => new Arr([ 'orphan' => new Arr(['ID' => 3, 'PID' => 333, 'alias' => 'orphan'])]),
            'keys' => ['all','roots','orphans','structure'],
            'first_root' => new Arr([
                'ID' => 1, 
                'PID' => null, 
                'alias' => 'root1',
                '__CHILDREN__' => new Arr([
                    'child11' => new Arr([
                        'ID' => 11, 
                        'PID' => 1, 
                        'alias' => 'child11',
                        '__CHILDREN__' => new Arr([
                            'child111' => new Arr(['ID' => 111, 'PID' => 11, 'alias' => 'child111']),
                        ])            
                    ])
                ])
            ])
        ];

        $arr = new Arr( $raw );
        $tree = $arr->asTree( 'ID', 'PID', 'alias', '__CHILDREN__' );

        $this->assertEquals( array_keys( $tree->getArrayCopy() ), $cmp['keys'] );
        $this->assertEquals( $tree['orphans'], $cmp['orphans'] );
        $this->assertEquals( $tree['roots']->hasKey('root1'), true );
        $this->assertEquals( $tree['roots'][ 'root1' ], $cmp['first_root'] );
        $this->assertEquals( $tree['structure'], $cmp_structure );

    }

    public function testToTreeLoop(): void
    {
        $raw = [
            // loop a-b-a
            ['id' => 1, 'parent_id' => 11, 'name' => 'loop1'],
            ['id' => 11, 'parent_id' => 1, 'name' => 'loop11'],
            // loop a-b-c-a
            ['id' => 2, 'parent_id' => 222, 'name' => 'loop2'],
            ['id' => 22, 'parent_id' => 2, 'name' => 'loop22'],
            ['id' => 222, 'parent_id' => 22, 'name' => 'loop222'],
        ];
        $ref = [
            'loop1' => ['id' => 1, 'parent_id' => 11, 'name' => 'loop1'],
            'loop11' => ['id' => 11, 'parent_id' => 1, 'name' => 'loop11'],
            // loop a-b-c-a
            'loop2' => ['id' => 2, 'parent_id' => 222, 'name' => 'loop2'],
            'loop22' => ['id' => 22, 'parent_id' => 2, 'name' => 'loop22'],
            'loop222' => ['id' => 222, 'parent_id' => 22, 'name' => 'loop222'],
        ];

        $tree = Arr::array_as_tree( $raw );
        $this->assertTrue( empty($tree['roots']) );

        $arr = new Arr( $raw );
        $this->assertTrue( $arr->asTree()['roots']->isEmpty() );

    }

    public function testShortest(): void
    {

        $raw = [
            [1,2]
            ,[3,4,5]
            ,[5,6,7]
            ,[8,9]
        ];

        $this->assertEquals( Arr::array_shortest( $raw ), [1,2] );
        $this->assertEquals( Arr::array_shortest( $raw, false ), [8,9] );

        $arr = new Arr( $raw );
        $this->assertEquals( $arr->shortest(), new Arr([1,2]) );
        $this->assertEquals( $arr->shortest( false ), new Arr([8,9]) );



    }

    public function testExtractValues(): void
    {
        $raw = [
            ['name' => 'a', 'id' => 1],
            ['name' => 'b', 'id' => 2],
            ['name' => 'c', 'id' => 3],
        ];

        $this->assertEquals( Arr::array_extract_values( $raw, 'name' ), ['a','b','c'] );

        $arr = new Arr($raw);
        $this->assertEquals( $arr->extractValues('id' ), new Arr([1,2,3]) );

    }

    public function testReindex(): void
    {
        $raw = [
            5 => [ 'name' => 'the_five' ],
            2 => [ 'name' => 'the_two', ],
            0 => [ 'name' => 'the_zero' ],
        ];
        $cmp = [
            'the_five' => [ 'name' => 'the_five' ],
            'the_two' => [ 'name' => 'the_two', ],
            'the_zero' => [ 'name' => 'the_zero' ],
        ];

        $this->assertEquals( Arr::array_reindex( $raw, 'name' ), $cmp );

        $arr = new Arr( $raw );
        $this->assertEquals( $arr->reindex( 'name' ), new Arr($cmp) );



    }

    public function testDeprefixKeys(): void
    {
        $raw = [
            'prefix->key' => 'value1' ,
            'prefix->key2' => 'value2' ,
            'NONprefix->key' => 'value3',
        ];
        $cmp1 = [
            'key' => 'value1' ,
            'key2' => 'value2' ,
        ];

        $cmp2 = [
            'key' => 'value1' ,
            'key2' => 'value2' ,
            'NONprefix->key' => 'value3',
        ];

        $this->assertEquals( Arr::array_deprefix_keys( $raw, 'prefix->' ), $cmp1 );

        $arr = new Arr( $raw );
        $this->assertEquals( $arr->deprefixKeys('prefix->', true ), new Arr($cmp2) );

    }

    public function testIntersectionOffset(): void
    {
        $raw10 = [0,1,2,3,5,6];
        // extra element at the end should be a stopper
        $raw11 = [0,1,2,3,5,6,0];
        $raw20 =       [3,5,6,7,8,9];

        $this->assertEquals( Arr::array_intersection_offset( $raw10, $raw20 ), ['start' => 3, 'end' => 6] );
        $this->assertEquals( Arr::array_intersection_offset( $raw11, $raw20 ), null );


        $raw30 = [0,1,2,0,1,2];
        $raw40 =         [1,2,0];

        $arr = new Arr( $raw30 );
        $this->assertEquals( $arr->intersectionOffset( $raw40 ), new Arr(['start' => 4, 'end' => 6]) );


    }

}