<?php

declare(strict_types=1);

namespace Tempest\Support\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Tempest\Support\ArrayHelper;
use Tempest\Support\InvalidMapWithKeysUsage;
use function Tempest\Support\arr;
use function Tempest\Support\str;

/**
 * @internal
 */
final class ArrayHelperTest extends TestCase
{
    public function test_wrap(): void
    {
        $this->assertTrue(arr()->equals([]));
        $this->assertTrue(arr('a')->equals(['a']));
        $this->assertTrue(arr(arr('a'))->equals(['a']));
        $this->assertTrue(arr(['a'])->equals(['a']));
    }

    public function test_to_array(): void
    {
        $this->assertSame(['a'], arr('a')->toArray());
    }

    public function test_loop(): void
    {
        $i = 0;

        foreach (arr(['a', 'b']) as $value) {
            $i++;
        }

        $this->assertSame(2, $i);
    }

    public function test_count(): void
    {
        $this->assertSame(2, arr(['a', 'b'])->count());
    }

    public function test_serialize(): void
    {
        $array = ['a', 'b'];

        $this->assertTrue(arr($array)->equals(unserialize(serialize($array))));
    }

    public function test_array_access(): void
    {
        $array = arr(['a' => 1, 'b' => 2]);

        $this->assertSame(1, $array['a']);
        $this->assertSame(2, $array['b']);
        $this->assertTrue(isset($array['a']));
        $this->assertFalse(isset($array['x']));

        unset($array['a']);
        $this->assertFalse(isset($array['a']));
    }

    public function test_get_dot(): void
    {
        $array = [
            'a' => [
                'b' => 'c',
            ],
        ];

        $this->assertSame('c', arr($array)->get('a.b'));
        $this->assertInstanceOf(ArrayHelper::class, arr($array)->get('a'));
        $this->assertNull(arr($array)->get('a.x'));
        $this->assertSame('default', arr($array)->get('a.x', 'default'));
    }

    public function test_get(): void
    {
        $array = [
            'b.c' => 'd',
            'a' => 'b',
        ];

        $this->assertSame('d', arr($array)->get('b.c'));
        $this->assertSame('b', arr($array)->get('a'));
    }

    public function test_arr_has(): void
    {
        $array = [
            'a' => [
                'b' => 'c',
            ],
        ];

        $this->assertTrue(arr($array)->has('a.b'));
        $this->assertTrue(arr($array)->has('a'));
        $this->assertFalse(arr($array)->has('a.x'));
    }

    public function test_arr_set(): void
    {
        $array = [
            'a' => [
                'b' => [
                    'c' => 'c',
                ],
            ],
        ];

        $this->assertTrue(arr()->set('a.b.c', 'c')->equals($array));
        $this->assertTrue(arr($array)->set('a', 'c')->equals(['a' => 'c']));
    }

    public function test_arr_put_is_alias_of_set(): void
    {
        $array = [
            'a' => [
                'b' => [
                    'c' => 'c',
                ],
            ],
        ];

        $this->assertTrue(arr()->set('a.b.c', 'c')->equals($array));
        $this->assertTrue(arr()->put('a.b.c', 'c')->equals($array));
        $this->assertTrue(arr($array)->set('a', 'c')->equals(['a' => 'c']));
        $this->assertTrue(arr($array)->put('a', 'c')->equals(['a' => 'c']));
    }

    public function test_unwrap(): void
    {
        $expected = [
            'a' => [
                'b' => [
                    'c' => 'c',
                ],
            ],
        ];

        $input = [
            'a.b.c' => 'c',
        ];

        $this->assertTrue(arr($input)->unwrap()->equals($expected));
    }

    public function test_implode(): void
    {
        $this->assertEquals(str('a,b,c'), arr(['a', 'b', 'c'])->implode(','));
    }

    #[TestWith([['Jon', 'Jane'], 'Jon and Jane'])]
    #[TestWith([['Jon', 'Jane', 'Jill'], 'Jon, Jane and Jill'])]
    public function test_join(array $initial, string $expected): void
    {
        $this->assertEquals($expected, arr($initial)->join());
    }

    #[TestWith([['Jon', 'Jane'], ', ', ' and maybe ', 'Jon and maybe Jane'])]
    #[TestWith([['Jon', 'Jane', 'Jill'], ' + ', ' and ', 'Jon + Jane and Jill'])]
    #[TestWith([['Jon', 'Jane', 'Jill'], ' + ', null, 'Jon + Jane + Jill'])]
    public function test_join_with_glues(array $initial, string $glue, ?string $finalGlue, string $expected): void
    {
        $this->assertTrue(arr($initial)->join($glue, $finalGlue)->equals($expected));
    }

    public function test_pop(): void
    {
        $array = arr(['a', 'b', 'c'])->pop($value);

        $this->assertSame('c', $value);
        $this->assertTrue($array->equals(['a', 'b']));

        $this->assertTrue(arr(['a', 'b', 'c'])->pop()->equals(['a', 'b']));
        $this->assertTrue(arr()->pop()->isEmpty());

        arr()->pop($value);
        $this->assertNull($value);
    }

    public function test_unshift(): void
    {
        $array = arr(['a', 'b', 'c'])->unshift($value);

        $this->assertSame('a', $value);
        $this->assertTrue($array->equals(['b', 'c']));

        $this->assertTrue(arr(['a', 'b', 'c'])->unshift()->equals(['b', 'c']));
        $this->assertTrue(arr()->unshift()->isEmpty());

        arr()->unshift($value);
        $this->assertNull($value);
    }

    public function test_last(): void
    {
        $this->assertSame(null, arr()->last());
        $this->assertSame('c', arr(['a', 'b', 'c'])->last());
    }

    public function test_first(): void
    {
        $this->assertSame('a', arr(['a', 'b', 'c'])->first());
        $this->assertSame(null, arr()->first());
    }

    public function test_is_empty(): void
    {
        $this->assertTrue(arr()->isEmpty());
        $this->assertFalse(arr(['a'])->isEmpty());
    }

    public function test_map(): void
    {
        $this->assertTrue(
            arr(['a', 'b'])
                ->map(fn (string $value) => $value . 'x')
                ->equals(['ax', 'bx']),
        );

        $this->assertTrue(
            arr(['a', 'b'])
                ->map(fn (string $value, mixed $key) => $value . $key)
                ->equals(['a0', 'b1']),
        );
    }

    public function test_map_with_keys(): void
    {
        $this->assertTrue(
            arr(['a', 'b'])
                ->mapWithKeys(fn (mixed $value, mixed $key) => yield $value => $value)
                ->equals(['a' => 'a', 'b' => 'b']),
        );

        $this->assertTrue(
            arr(['a' => 'a', 'b' => 'b'])
                ->mapWithKeys(fn (mixed $value, mixed $key) => yield $value)
                ->equals(['b']),
        );
    }

    public function test_map_with_keys_without_generator(): void
    {
        $this->expectException(InvalidMapWithKeysUsage::class);

        arr(['a', 'b'])
            ->mapWithKeys(fn (mixed $value, mixed $key) => $value);
    }

    public function test_values(): void
    {
        $this->assertTrue(
            arr(['a' => 'a', 'b' => 'b'])
                ->values()
                ->equals(['a', 'b']),
        );
    }

    public function test_filter(): void
    {
        $this->assertSame(
            ['a', 'b', '-1', -1, '0', 0],
            arr(['a', false, 'b', '-1', null, -1, '0', 0])
                ->filter()
                ->values()
                ->toArray(),
        );

        $this->assertTrue(
            arr(['a', 'b', 'c'])
                ->filter(fn (mixed $value) => $value === 'b')
                ->values()
                ->equals(['b']),
        );

        $this->assertTrue(
            arr(['a', 'b', 'c'])
                ->filter(fn (mixed $value, mixed $key) => $key === 1)
                ->values()
                ->equals(['b']),
        );
    }

    public function test_reverse(): void
    {
        $this->assertTrue(
            arr(['a', 'b', 'c'])
                ->reverse()
                ->equals(['c', 'b', 'a']),
        );
    }

    public function test_each(): void
    {
        $string = '';

        arr(['a', 'b', 'c'])->each(function (mixed $value) use (&$string): void {
            $string .= $value;
        });

        $this->assertSame('abc', $string);

        $string = '';

        arr(['a', 'b', 'c'])->each(function (mixed $value, mixed $key) use (&$string): void {
            $string .= $key;
        });

        $this->assertSame('012', $string);
    }

    public function test_contains(): void
    {
        $this->assertTrue(arr(['a', 'b', 'c'])->contains('b'));
        $this->assertFalse(arr(['a', 'b', 'c'])->contains('d'));
    }

    public function test_explode(): void
    {
        $this->assertEquals(['john', 'doe'], ArrayHelper::explode('john doe')->toArray());
        $this->assertEquals(['john', 'doe'], ArrayHelper::explode(str('john doe'))->toArray());
        $this->assertEquals(['john doe'], ArrayHelper::explode('john doe', ',')->toArray());
        $this->assertEquals(['john', 'doe'], ArrayHelper::explode('john, doe', ', ')->toArray());
        $this->assertEquals(['john, doe'], ArrayHelper::explode('john, doe', '')->toArray());
    }

    public function test_combine_with_integers(): void
    {
        $collection = arr([1, 2, 3]);
        $current = $collection
            ->combine([4, 5, 6])
            ->toArray();
        $expected = [
            1 => 4,
            2 => 5,
            3 => 6,
        ];

        $this->assertSame($expected, $current);
    }

    public function test_combine_with_strings(): void
    {
        $collection = arr([
            'first_name',
            'last_name',
        ]);
        $current = $collection
            ->combine([
                'John',
                'Doe',
            ])
            ->toArray();
        $expected = [
            'first_name' => 'John',
            'last_name' => 'Doe',
        ];

        $this->assertSame($expected, $current);
    }

    public function test_combine_with_associative_arrays(): void
    {
        $collection = arr([
            5 => 'first_name',
            'test' => 'last_name',
            42 => 'age',
        ]);
        $current = $collection
            ->combine([
                4 => 'John',
                5 => 'Doe',
                6 => 50,
            ])
            ->toArray();
        $expected = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'age' => 50,
        ];

        $this->assertSame($expected, $current);
    }

    public function test_combine_with_collection(): void
    {
        $collection = arr(['first_name', 'last_name']);
        $another_collection = arr(['John', 'Doe']);
        $current = $collection
            ->combine($another_collection)
            ->toArray();
        $expected = [
            'first_name' => 'John',
            'last_name' => 'Doe',
        ];

        $this->assertSame($expected, $current);
    }

    public function test_keys(): void
    {
        $collection = arr([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'framework' => 'Tempest',
        ]);
        $current = $collection
            ->keys()
            ->toArray();
        $expected = [
            'first_name',
            'last_name',
            'framework',
        ];

        $this->assertSame($expected, $current);
    }

    public function test_merge_array(): void
    {
        $collection = arr([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        $current = $collection
            ->merge([
                'framework' => 'Tempest',
            ])
            ->toArray();
        $expected = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'framework' => 'Tempest',
        ];

        $this->assertSame($expected, $current);
    }

    public function test_merge_collection(): void
    {
        $collection = arr([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        $current = $collection
            ->merge(arr([
                'framework' => 'Tempest',
            ]))
            ->toArray();
        $expected = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'framework' => 'Tempest',
        ];

        $this->assertSame($expected, $current);
    }

    public function test_diff_values(): void
    {
        $collection = arr([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'age' => 42,
        ]);
        $current = $collection
            ->diff([
                'John',
                'Doe',
            ])
            ->toArray();
        $expected = [
            'age' => 42,
        ];

        $this->assertSame($expected, $current);
    }

    public function test_diff_keys(): void
    {
        $collection = arr([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'age' => 42,
        ]);
        $current = $collection
            ->diffKeys([
                'age' => 10,
            ])
            ->toArray();
        $expected = [
            'first_name' => 'John',
            'last_name' => 'Doe',
        ];

        $this->assertSame($expected, $current);
    }

    public function test_intersect(): void
    {
        $collection = arr([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'age' => 42,
        ]);
        $current = $collection
            ->intersect([
                'John',
                'Doe',
            ])
            ->toArray();
        $expected = [
            'first_name' => 'John',
            'last_name' => 'Doe',
        ];

        $this->assertSame($expected, $current);
    }

    public function test_intersect_keys(): void
    {
        $collection = arr([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'age' => 42,
        ]);
        $current = $collection
            ->intersectKeys([
                'first_name' => true,
                'last_name' => true,
            ])
            ->toArray();
        $expected = [
            'first_name' => 'John',
            'last_name' => 'Doe',
        ];

        $this->assertSame($expected, $current);
    }

    public function test_unique_with_basic_item(): void
    {
        $collection = arr([
            'John',
            'Doe',
            'John',
            'Doe',
            'Jane',
            'Doe',
        ]);
        $current = $collection
            ->unique()
            ->values()
            ->toArray();
        $expected = [
            'John',
            'Doe',
            'Jane',
        ];

        $this->assertSame($expected, $current);
    }

    public function test_unique_callback(): void
    {
        $collection = arr([
            'John',
            'Doe',
            'John',
            'Doe',
            'Jane',
            'Doe',
        ]);

        $current = $collection
            ->unique(fn (string $item) => $item[0])
            ->values()
            ->toArray();

        $expected = [
            'John',
            'Doe',
        ];

        $this->assertSame($expected, $current);
    }

    public function test_unique_with_associative_array(): void
    {
        $collection = arr([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'age' => 42,
            'steal_name' => 'John',
        ]);
        $current = $collection
            ->unique()
            ->toArray();
        $expected = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'age' => 42,
        ];

        $this->assertSame($expected, $current);
    }

    public function test_unique_with_arrays(): void
    {
        $collection = arr([
            ['John', 'Doe'],
            ['John', 'Doe'],
            [1, 2],
            [1, 2],
            [3, 4],
        ]);
        $current = $collection
            ->unique()
            ->values()
            ->toArray();
        $expected = [
            ['John', 'Doe'],
            [1, 2],
            [3, 4],
        ];

        $this->assertSame($expected, $current);
    }

    public function test_unique_with_key(): void
    {
        $collection = arr([
            ['id' => 1, 'first_name' => 'John', 'last_name' => 'Doe'],
            ['id' => 2, 'first_name' => 'John', 'last_name' => 'Doe'],
            ['id' => 3, 'first_name' => 'Jane', 'last_name' => 'Doe'],
            ['id' => 3, 'first_name' => 'Jane', 'last_name' => 'Duplicate'],
        ]);

        $this->assertSame(
            $collection
                ->unique('first_name')
                ->values()
                ->toArray(),
            [
                ['id' => 1, 'first_name' => 'John', 'last_name' => 'Doe'],
                ['id' => 3, 'first_name' => 'Jane', 'last_name' => 'Doe'],
            ],
        );

        $this->assertSame(
            $collection
                ->unique('last_name')
                ->values()
                ->toArray(),
            [
                ['id' => 1, 'first_name' => 'John', 'last_name' => 'Doe'],
                ['id' => 3, 'first_name' => 'Jane', 'last_name' => 'Duplicate'],
            ],
        );

        $this->assertSame(
            $collection
                ->unique('id')
                ->values()
                ->toArray(),
            [
                ['id' => 1, 'first_name' => 'John', 'last_name' => 'Doe'],
                ['id' => 2, 'first_name' => 'John', 'last_name' => 'Doe'],
                ['id' => 3, 'first_name' => 'Jane', 'last_name' => 'Doe'],
            ],
        );
    }

    public function test_unique_ensure_unnested_value_is_rejected_when_key_is_set(): void
    {
        $collection = arr([
            42,
            'Hello World',
            ['id' => 1, 'first_name' => 'John', 'last_name' => 'Doe'],
            ['id' => 2, 'first_name' => 'John', 'last_name' => 'Doe'],
            ['id' => 3, 'first_name' => 'Jane', 'last_name' => 'Doe'],
            ['id' => 3, 'first_name' => 'Jane', 'last_name' => 'Duplicate'],
        ]);

        $this->assertSame(
            $collection
                ->unique('id')
                ->values()
                ->toArray(),
            [
                ['id' => 1, 'first_name' => 'John', 'last_name' => 'Doe'],
                ['id' => 2, 'first_name' => 'John', 'last_name' => 'Doe'],
                ['id' => 3, 'first_name' => 'Jane', 'last_name' => 'Doe'],
            ],
        );
    }

    public function test_unique_unstrict_check(): void
    {
        $this->assertSame(
            arr([
                42,
                '42',
                true,
                'true',
            ])
                ->unique(shouldBeStrict: false)
                ->values()
                ->toArray(),
            [
                42,
                'true',
            ],
        );
    }

    public function test_unique_strict_check(): void
    {
        $this->assertSame(
            arr([
                42,
                '42',
                true,
                'true',
            ])
                ->unique(shouldBeStrict: true)
                ->values()
                ->toArray(),
            [
                42,
                '42',
                true,
                'true',
            ],
        );
    }

    public function test_unique_with_key_and_strict_check(): void
    {
        $this->assertSame(
            arr([
                ['id' => 1, 'first_name' => 'John', 'last_name' => 'Doe'],
                ['id' => '1', 'first_name' => 'John', 'last_name' => 'Doe'],
                ['id' => 2, 'first_name' => 'John', 'last_name' => 'Doe'],
                ['id' => 3, 'first_name' => 'Jane', 'last_name' => 'Doe'],
                ['id' => 3, 'first_name' => 'Jane', 'last_name' => 'Duplicate'],
            ])
                ->unique('id', shouldBeStrict: true)
                ->values()
                ->toArray(),
            [
                ['id' => 1, 'first_name' => 'John', 'last_name' => 'Doe'],
                ['id' => '1', 'first_name' => 'John', 'last_name' => 'Doe'],
                ['id' => 2, 'first_name' => 'John', 'last_name' => 'Doe'],
                ['id' => 3, 'first_name' => 'Jane', 'last_name' => 'Doe'],
            ],
        );
    }

    public function test_unique_key_dot_notation(): void
    {
        $collection = arr([
            [
                'id' => 1,
                'title' => 'First Post',
                'author' => ['id' => 1, 'name' => 'John Doe'],
            ],
            [
                'id' => 2,
                'title' => 'Second Post',
                'author' => ['id' => 2, 'name' => 'Jane Smith'],
            ],
            [
                'id' => 3,
                'title' => 'Third Post',
                'author' => ['id' => 1, 'name' => 'John Doe'],  // Duplicate author
            ],
            [
                'id' => 4,
                'title' => 'Fourth Post',
                'author' => ['id' => 3, 'name' => 'Alice Johnson'],
            ],
            [
                'id' => 5,
                'title' => 'Fifth Post',
                'author' => ['id' => 2, 'name' => 'Jane Smith'],  // Duplicate author
            ],
            [
                'id' => 6,
                'title' => 'Sixth Post',
                'author' => ['id' => 4, 'name' => 'Bob Brown'],
            ],
        ]);

        $this->assertSame(
            $collection
                ->unique('author.id')
                ->values()
                ->toArray(),
            [
                [
                    'id' => 1,
                    'title' => 'First Post',
                    'author' => ['id' => 1, 'name' => 'John Doe'],
                ],
                [
                    'id' => 2,
                    'title' => 'Second Post',
                    'author' => ['id' => 2, 'name' => 'Jane Smith'],
                ],
                [
                    'id' => 4,
                    'title' => 'Fourth Post',
                    'author' => ['id' => 3, 'name' => 'Alice Johnson'],
                ],
                [
                    'id' => 6,
                    'title' => 'Sixth Post',
                    'author' => ['id' => 4, 'name' => 'Bob Brown'],
                ],
            ],
        );
    }

    public function test_flip(): void
    {
        $this->assertSame(
            arr([
                'first_name' => 'John',
                'last_name' => 'Doe',
            ])
                ->flip()
                ->toArray(),
            [
                'John' => 'first_name',
                'Doe' => 'last_name',
            ],
        );
    }

    public function test_pad(): void
    {
        $this->assertSame(
            arr([1, 2, 3])
                ->pad(4, 0)
                ->toArray(),
            [1, 2, 3, 0],
        );

        $this->assertSame(
            arr([1, 2, 3, 4, 5])
                ->pad(4, 0)
                ->toArray(),
            [1, 2, 3, 4, 5],
        );

        $this->assertSame(
            arr([1, 2, 3])
                ->pad(-4, 0)
                ->toArray(),
            [0, 1, 2, 3],
        );

        $this->assertSame(
            arr([1, 2, 3, 4, 5])
                ->pad(-4, 0)
                ->toArray(),
            [1, 2, 3, 4, 5],
        );
    }

    public function test_add(): void
    {
        $collection = arr();
        $this->assertSame(
            $collection
                ->add(1)
                ->toArray(),
            [1],
        );

        $this->assertSame(
            $collection
                ->add(2)
                ->toArray(),
            [1, 2],
        );

        $this->assertSame(
            $collection
                ->add('')
                ->toArray(),
            [1, 2, ''],
        );

        $this->assertSame(
            $collection
                ->add(null)
                ->toArray(),
            [1, 2, '', null],
        );

        $this->assertSame(
            $collection
                ->add(false)
                ->toArray(),
            [1, 2, '', null, false],
        );

        $this->assertSame(
            $collection
                ->add([])
                ->toArray(),
            [1, 2, '', null, false, []],
        );

        $this->assertSame(
            actual: $collection
                ->add('name')
                ->toArray(),
            expected: [1, 2, '', null, false, [], 'name'],
        );
    }

    public function test_push_is_alias_of_add(): void
    {
        $first_collection = arr()
            ->add(42)
            ->add('Hello')
            ->add([])
            ->add(false)
            ->add(null);
        $second_collection = arr()
            ->push(42)
            ->push('Hello')
            ->push([])
            ->push(false)
            ->push(null);

        $this->assertTrue($first_collection->equals($second_collection));
    }

    public function test_pluck_without_arrays(): void
    {
        $this->assertSame(
            arr([
                'name' => 'John',
                'age' => 42,
            ])
                ->pluck('name')
                ->toArray(),
            [],
        );
    }

    public function test_pluck_basics(): void
    {
        $collection = arr([
            ['name' => 'John', 'age' => 42],
            ['name' => 'Jane', 'age' => 35],
            ['name' => 'Alice', 'age' => 28],
        ]);

        $this->assertSame(
            $collection
                ->pluck('name')
                ->toArray(),
            ['John', 'Jane', 'Alice'],
        );

        $this->assertSame(
            $collection
                ->pluck('age')
                ->toArray(),
            [42, 35, 28],
        );

        $this->assertSame(
            $collection
                ->pluck('name', 'age')
                ->toArray(),
            [
                42 => 'John',
                35 => 'Jane',
                28 => 'Alice',
            ],
        );

        $this->assertSame(
            $collection
                ->pluck('age', 'name')
                ->toArray(),
            [
                'John' => 42,
                'Jane' => 35,
                'Alice' => 28,
            ],
        );
    }

    public function test_pluck_dot_notation(): void
    {
        $collection = arr([
            [
                'id' => 1,
                'title' => 'First Post',
                'author' => ['id' => 1, 'name' => 'John Doe'],
            ],
            [
                'id' => 2,
                'title' => 'Second Post',
                'author' => ['id' => 2, 'name' => 'Jane Smith'],
            ],
            [
                'id' => 3,
                'title' => 'Third Post',
                'author' => ['id' => 1, 'name' => 'John Doe'],
            ],
            [
                'id' => 4,
                'title' => 'Fourth Post',
                'author' => ['id' => 3, 'name' => 'Alice Johnson'],
            ],
            [
                'id' => 5,
                'title' => 'Fifth Post',
                'author' => ['id' => 2, 'name' => 'Jane Smith'],
            ],
            [
                'id' => 6,
                'title' => 'Sixth Post',
                'author' => ['id' => 4, 'name' => 'Bob Brown'],
            ],
        ]);

        $this->assertSame(
            $collection
                ->pluck('author.name')
                ->toArray(),
            [
                'John Doe',
                'Jane Smith',
                'John Doe',
                'Alice Johnson',
                'Jane Smith',
                'Bob Brown',
            ],
        );

        $this->assertSame(
            $collection
                ->pluck('author.name', 'id')
                ->toArray(),
            [
                1 => 'John Doe',
                2 => 'Jane Smith',
                3 => 'John Doe',
                4 => 'Alice Johnson',
                5 => 'Jane Smith',
                6 => 'Bob Brown',
            ],
        );

        $this->assertSame(
            $collection
                ->pluck('author.name', 'author.id')
                ->toArray(),
            [
                1 => 'John Doe',
                2 => 'Jane Smith',
                3 => 'Alice Johnson',
                4 => 'Bob Brown',
            ],
        );
    }

    public function test_random(): void
    {
        $collection = arr([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);

        $random = $collection->random();
        $this->assertIsInt($random);
        $this->assertContains($random, $collection->toArray());

        $randoms = $collection->random(3);
        foreach ($randoms as $value) {
            $this->assertIsInt($value);
            $this->assertContains($value, $collection->toArray());
        }

        $this->assertCount(3, $randoms);
    }

    public function test_random_with_preserve_keys(): void
    {
        $collection = arr([
            'id' => 1,
            'title' => 'First Post',
            'author' => ['id' => 1, 'name' => 'John Doe'],
        ]);

        $randoms = $collection->random(3, preserveKey: true);

        $this->assertCount(3, $randoms);
        $this->assertArrayHasKey('id', $randoms);
        $this->assertArrayHasKey('title', $randoms);
        $this->assertArrayHasKey('author', $randoms);

        $randoms = $collection->random(2, preserveKey: true);

        $this->assertCount(2, array_intersect_key($collection->toArray(), $randoms->toArray()));
    }

    public function test_random_on_empty_array(): void
    {
        $collection = arr();

        $this->expectException(InvalidArgumentException::class);

        $collection->random();
    }

    public function test_random_with_count_superior_than_array_count(): void
    {
        $collection = arr([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);

        $this->expectException(InvalidArgumentException::class);

        $collection->random(15);
    }

    public function test_random_throw_exception_when_giving_negative_integer(): void
    {
        $collection = arr([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);

        $this->expectException(InvalidArgumentException::class);

        $collection->random(-1);
    }

    public function test_is_list(): void
    {
        $this->assertTrue(arr()->isList());
        $this->assertTrue(arr(['a', 2, 3])->isList());
        $this->assertTrue(arr([0 => 'a', 'b'])->isList());

        $this->assertFalse(arr([1 => 'a', 'b'])->isList());
        $this->assertFalse(arr([1 => 'a', 0 => 'b'])->isList());
        $this->assertFalse(arr([0 => 'a', 'foo' => 'b'])->isList());
        $this->assertFalse(arr([0 => 'a', 2 => 'b'])->isList());
    }

    public function test_is_assoc(): void
    {
        $this->assertTrue(arr([1 => 'a', 'b'])->isAssoc());
        $this->assertTrue(arr([1 => 'a', 0 => 'b'])->isAssoc());
        $this->assertTrue(arr([0 => 'a', 'foo' => 'b'])->isAssoc());
        $this->assertTrue(arr([0 => 'a', 2 => 'b'])->isAssoc());

        $this->assertFalse(arr()->isAssoc());
        $this->assertFalse(arr([1, 2, 3])->isAssoc());
        $this->assertFalse(arr(['a', 2, 3])->isAssoc());
        $this->assertFalse(arr([0 => 'a', 'b'])->isAssoc());

        $this->assertTrue(arr([0 => 'a', 'foo' => 'b'])->isAssoc());
        $this->assertTrue(arr([0 => 'a', 2 => 'b'])->isAssoc());
        $this->assertTrue(arr(['foo' => 'a', 'baz' => 'b'])->isAssoc());
    }

    public function test_remove_with_basic_keys(): void
    {
        $collection = arr([1, 2, 3]);

        $this->assertEquals(
            $collection
                ->remove(1)
                ->toArray(),
            [
                0 => 1,
                2 => 3,
            ],
        );

        $this->assertEquals(
            $collection
                ->remove([0, 2])
                ->toArray(),
            [],
        );
    }

    public function test_remove_with_associative_keys(): void
    {
        $collection = arr([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'age' => 42,
        ]);

        $this->assertEquals(
            $collection
                ->remove('first_name')
                ->toArray(),
            [
                'last_name' => 'Doe',
                'age' => 42,
            ],
        );

        $this->assertEquals(
            $collection
                ->remove(['last_name', 'age'])
                ->toArray(),
            [],
        );
    }

    public function test_remove_with_no_valid_key(): void
    {
        $collection = arr([1, 2, 3]);

        $this->assertEquals(
            $collection
                ->remove(42)
                ->toArray(),
            [1, 2, 3],
        );

        $collection = arr([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'age' => 42,
        ]);

        $this->assertEquals(
            $collection
                ->remove('foo')
                ->toArray(),
            [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'age' => 42,
            ],
        );

        $this->assertEquals(
            $collection
                ->remove(['bar', 'first_name'])
                ->toArray(),
            [
                'last_name' => 'Doe',
                'age' => 42,
            ],
        );
    }

    public function test_forget_is_alias_of_remove(): void
    {
        $first_collection = arr([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'age' => 42,
        ])
            ->remove(42)
            ->remove('foo')
            ->remove(['bar', 'first_name']);

        $second_collection = arr([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'age' => 42,
        ])
            ->forget(42)
            ->forget('foo')
            ->forget(['bar', 'first_name']);

        $this->assertTrue($first_collection->equals($second_collection));
    }

    public function test_shuffle_actually_shuffles(): void
    {
        $array = range('a', 'z');

        $this->assertNotEquals(arr($array)->shuffle()->toArray(), $array);
        $this->assertNotEquals(arr($array)->shuffle()->toArray(), $array);
    }

    public function test_shuffle_keeps_same_values(): void
    {
        $array = range('a', 'z');
        $shuffled = arr($array)->shuffle()->toArray();
        sort($shuffled);

        $this->assertSame($shuffled, $array);
    }

    public function test_pull(): void
    {
        $collection = arr([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'age' => 42,
        ]);

        $this->assertSame(
            $collection->pull('first_name'),
            'John',
        );

        $this->assertSame(
            $collection->toArray(),
            [
                'last_name' => 'Doe',
                'age' => 42,
            ],
        );
    }

    public function test_sort(): void
    {
        $array = arr([1 => 'c', 2 => 'a', 3 => 'b']);

        // Test auto-detects key preservation
        $this->assertSame(
            ['a', 'b', 'c'],
            arr(['c', 'a', 'b'])->sort()->toArray(),
        );
        $this->assertSame(
            [2 => 'a', 3 => 'b', 1 => 'c'],
            $array->sort()->toArray(),
        );

        $this->assertSame(
            ['a', 'b', 'c'],
            $array->sort(desc: false, preserveKeys: false)->toArray(),
        );
        $this->assertSame(
            ['c', 'b', 'a'],
            $array->sort(desc: true, preserveKeys: false)->toArray(),
        );

        $this->assertSame(
            [2 => 'a', 3 => 'b', 1 => 'c'],
            $array->sort(desc: false, preserveKeys: true)->toArray(),
        );
        $this->assertSame(
            [1 => 'c', 3 => 'b', 2 => 'a'],
            $array->sort(desc: true, preserveKeys: true)->toArray(),
        );
    }

    public function test_sort_by_callback(): void
    {
        $array = arr([1 => 'c', 2 => 'a', 3 => 'b']);

        // Test auto-detects key preservation
        $this->assertSame(
            ['a', 'b', 'c'],
            arr(['c', 'a', 'b'])->sortByCallback(fn ($a, $b) => $a <=> $b)->toArray(),
        );
        $this->assertSame(
            [2 => 'a', 3 => 'b', 1 => 'c'],
            $array->sortByCallback(fn ($a, $b) => $a <=> $b)->toArray(),
        );

        $this->assertSame(
            ['a', 'b', 'c'],
            $array->sortByCallback(
                callback: fn ($a, $b) => $a <=> $b,
                preserveKeys: false,
            )->toArray(),
        );
        $this->assertSame(
            [2 => 'a', 3 => 'b', 1 => 'c'],
            $array->sortByCallback(
                callback: fn ($a, $b) => $a <=> $b,
                preserveKeys: true,
            )->toArray(),
        );
    }

    public function test_sort_keys(): void
    {
        $array = arr([2 => 'a', 1 => 'c', 3 => 'b']);

        $this->assertSame(
            [1 => 'c', 2 => 'a', 3 => 'b'],
            $array->sortKeys(desc: false)->toArray(),
        );
        $this->assertSame(
            [3 => 'b', 2 => 'a', 1 => 'c'],
            $array->sortKeys(desc: true)->toArray(),
        );
    }

    public function test_sort_keys_by_callback(): void
    {
        $array = arr([2 => 'a', 1 => 'c', 3 => 'b']);

        $this->assertSame(
            [1 => 'c', 2 => 'a', 3 => 'b'],
            $array->sortKeysByCallback(fn ($a, $b) => $a <=> $b)->toArray(),
        );
    }

    public function test_flatten(): void
    {
        $this->assertTrue(arr(['#foo', '#bar', '#baz'])->flatten()->equals(['#foo', '#bar', '#baz']));
        $this->assertTrue(arr([['#foo', '#bar'], '#baz'])->flatten()->equals(['#foo', '#bar', '#baz']));
        $this->assertTrue(arr([['#foo', null], '#baz', null])->flatten()->equals(['#foo', null, '#baz', null]));
        $this->assertTrue(arr([['#foo', '#bar'], ['#baz']])->flatten()->equals(['#foo', '#bar', '#baz']));
        $this->assertTrue(arr([['#foo', ['#bar']], ['#baz']])->flatten()->equals(['#foo', '#bar', '#baz']));
        $this->assertTrue(arr([['#foo', ['#bar', ['#baz']]], '#zap'])->flatten()->equals(['#foo', '#bar', '#baz', '#zap']));

        $this->assertTrue(arr([['#foo', ['#bar', ['#baz']]], '#zap'])->flatten(depth: 1)->equals(['#foo', ['#bar', ['#baz']], '#zap']));
        $this->assertTrue(arr([['#foo', ['#bar', ['#baz']]], '#zap'])->flatten(depth: 2)->equals(['#foo', '#bar', ['#baz'], '#zap']));
    }

    public function test_flatmap(): void
    {
        // basic
        $this->assertTrue(
            arr([
                ['name' => 'Makise', 'hobbies' => ['Science', 'Programming']],
                ['name' => 'Okabe', 'hobbies' => ['Science', 'Anime']],
            ])->flatMap(fn (array $person) => $person['hobbies'])
                ->equals(['Science', 'Programming', 'Science', 'Anime']),
        );

        // deeply nested
        $likes = arr([
            ['name' => 'Enzo', 'likes' => [
                'manga' => ['Tower of God', 'The Beginning After The End'],
                'languages' => ['PHP', 'TypeScript'],
            ]],
            ['name' => 'Jon', 'likes' => [
                'manga' => ['One Piece', 'Naruto'],
                'languages' => ['Python'],
            ]],
        ]);

        $this->assertTrue(
            $likes->flatMap(fn (array $person) => $person['likes'], depth: 1)
                ->equals([
                    ['Tower of God', 'The Beginning After The End'],
                    ['PHP', 'TypeScript'],
                    ['One Piece', 'Naruto'],
                    ['Python'],
                ]),
        );

        $this->assertTrue(
            $likes->flatMap(fn (array $person) => $person['likes'], depth: INF)
                ->equals([
                    'Tower of God',
                    'The Beginning After The End',
                    'PHP',
                    'TypeScript',
                    'One Piece',
                    'Naruto',
                    'Python',
                ]),
        );
    }

    public function test_basic_reduce(): void
    {
        $collection = arr([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'age' => 42,
        ]);

        $this->assertSame(
            $collection->reduce(fn ($carry, $value) => $carry . ' ' . $value, 'Hello'),
            'Hello John Doe 42',
        );
    }

    public function test_reduce_with_existing_function(): void
    {
        $collection = arr([
            [1, 2, 2, 3],
            [2, 3, 3, 4],
            [3, 1, 3, 1],
        ]);

        $this->assertSame(
            $collection->reduce('max'),
            [3, 1, 3, 1],
        );
    }

    public function test_empty_array_reduce(): void
    {
        $this->assertSame(
            arr()->reduce(fn ($carry, $value) => $carry . ' ' . $value, 'default'),
            'default',
        );
    }

    public function test_chunk(): void
    {
        $collection = arr([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);

        $this->assertSame(
            $collection
                ->chunk(2, preserveKeys: false)
                ->map(fn ($chunk) => $chunk->toArray())
                ->toArray(),
            [
                [1, 2],
                [3, 4],
                [5, 6],
                [7, 8],
                [9, 10],
            ],
        );

        $this->assertSame(
            $collection
                ->chunk(3, preserveKeys: false)
                ->map(fn ($chunk) => $chunk->toArray())
                ->toArray(),
            [
                [1, 2, 3],
                [4, 5, 6],
                [7, 8, 9],
                [10],
            ],
        );
    }

    public function test_chunk_preserve_keys(): void
    {
        $collection = arr([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);

        $this->assertSame(
            $collection
                ->chunk(2)
                ->map(fn ($chunk) => $chunk->toArray())
                ->toArray(),
            [
                [0 => 1, 1 => 2],
                [2 => 3, 3 => 4],
                [4 => 5, 5 => 6],
                [6 => 7, 7 => 8],
                [8 => 9, 9 => 10],
            ],
        );

        $this->assertSame(
            $collection
                ->chunk(3)
                ->map(fn ($chunk) => $chunk->toArray())
                ->toArray(),
            [
                [0 => 1, 1 => 2, 2 => 3],
                [3 => 4, 4 => 5, 5 => 6],
                [6 => 7, 7 => 8, 8 => 9],
                [9 => 10],
            ],
        );
    }

    public function test_find_key_with_simple_value(): void
    {
        $collection = arr(['apple', 'banana', 'orange']);

        $this->assertSame(1, $collection->findKey('banana'));
        $this->assertSame(0, $collection->findKey('apple'));
        $this->assertNull($collection->findKey('grape'));
    }

    public function test_find_key_with_strict_comparison(): void
    {
        $collection = arr([1, '1', 2, '2']);

        $this->assertSame(0, $collection->findKey(1, strict: false));
        $this->assertSame(0, $collection->findKey('1', strict: false));

        $this->assertSame(0, $collection->findKey(1, strict: true));
        $this->assertSame(1, $collection->findKey('1', strict: true));
    }

    public function test_find_key_with_closure(): void
    {
        $collection = arr([
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Jane'],
            ['id' => 3, 'name' => 'Bob'],
        ]);

        $result = $collection->findKey(fn ($item) => $item['name'] === 'Jane');
        $this->assertSame(1, $result);

        $result = $collection->findKey(fn ($item, $key) => $key === 2);
        $this->assertSame(2, $result);

        $result = $collection->findKey(fn ($item) => $item['name'] === 'Alice');
        $this->assertNull($result);
    }

    public function test_find_key_with_string_keys(): void
    {
        $collection = arr([
            'first' => 'value1',
            'second' => 'value2',
            'third' => 'value3',
        ]);

        $this->assertSame('second', $collection->findKey('value2'));
        $this->assertNull($collection->findKey('value4'));
    }

    public function test_find_key_with_null_values(): void
    {
        $collection = arr(['a', null, 'b', '']);

        $this->assertSame(1, $collection->findKey(null));
        $this->assertSame(1, $collection->findKey(''));
    }

    public function test_find_key_with_complex_closure(): void
    {
        $collection = arr([
            ['age' => 25, 'active' => true],
            ['age' => 30, 'active' => false],
            ['age' => 35, 'active' => true],
        ]);

        $result = $collection->findKey(function ($item) {
            return $item['age'] > 28 && $item['active'] === true;
        });

        $this->assertSame(2, $result);
    }

    public function test_find_key_with_empty_array(): void
    {
        $collection = arr([]);

        $this->assertNull($collection->findKey('anything'));
        $this->assertNull($collection->findKey(fn () => true));
    }

    public function test_slice(): void
    {
        $collection = arr([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);

        $this->assertSame(
            $collection->slice(0, 3)->values()->toArray(),
            [1, 2, 3],
        );

        $this->assertSame(
            $collection->slice(3)->values()->toArray(),
            [4, 5, 6, 7, 8, 9, 10],
        );

        $this->assertSame(
            $collection->slice(-3)->values()->toArray(),
            [8, 9, 10],
        );

        $this->assertSame(
            $collection->slice(-3, 2)->values()->toArray(),
            [8, 9],
        );

        $this->assertSame(
            $collection->slice(-3, -1)->values()->toArray(),
            [8, 9],
        );
    }

    public function test_every(): void
    {
        $this->assertTrue(arr([])->every(fn (int $value) => ($value % 2) === 0));
        $this->assertTrue(arr([2, 4, 6])->every(fn (int $value) => ($value % 2) === 0));
        $this->assertFalse(arr([1, 2, 4, 6])->every(fn (int $value) => ($value % 2) === 0));
        $this->assertTrue(arr([0, 1, true, false, ''])->every());
        $this->assertFalse(arr([0, 1, true, false, '', null])->every());
    }

    public function test_append(): void
    {
        $collection = arr(['foo', 'bar']);

        $this->assertSame(
            actual: $collection->append('foo')->toArray(),
            expected: ['foo', 'bar', 'foo'],
        );

        $this->assertSame(
            actual: $collection->append(1, 'b')->toArray(),
            expected: ['foo', 'bar', 1, 'b'],
        );

        $this->assertSame(
            actual: $collection->append(['a' => 'b'])->toArray(),
            expected: ['foo', 'bar', ['a' => 'b']],
        );
    }

    public function test_prepend(): void
    {
        $collection = arr(['foo', 'bar']);

        $this->assertSame(
            actual: $collection->prepend('foo')->toArray(),
            expected: ['foo', 'foo', 'bar'],
        );

        $this->assertSame(
            actual: $collection->prepend(1, 'b')->toArray(),
            expected: [1, 'b', 'foo', 'bar'],
        );

        $this->assertSame(
            actual: $collection->prepend(['a' => 'b'])->toArray(),
            expected: [['a' => 'b'], 'foo', 'bar'],
        );
    }
}
