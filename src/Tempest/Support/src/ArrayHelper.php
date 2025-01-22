<?php

declare(strict_types=1);

namespace Tempest\Support;

use ArrayAccess;
use Closure;
use Countable;
use Generator;
use InvalidArgumentException;
use Iterator;
use Random\Randomizer;
use Serializable;
use Stringable;
use Tempest\Support\Conditions\HasConditions;
use function Tempest\map;

/**
 * @template TKey of array-key
 * @template TValue
 *
 * @implements ArrayAccess<TKey, TValue>
 * @implements Iterator<TKey, TValue>
 */
final class ArrayHelper implements Iterator, ArrayAccess, Serializable, Countable
{
    use HasConditions;
    use IsIterable;

    /** @var array<TKey, TValue> */
    private array $array;

    /**
     * @param array<TKey, TValue>|self<TKey, TValue>|TValue $input
     */
    public function __construct(
        mixed $input = [],
    ) {
        if (is_array($input)) {
            $this->array = $input;
        } elseif ($input instanceof self) {
            $this->array = $input->array;
        } elseif ($input === null) {
            $this->array = [];
        } else {
            $this->array = [$input];
        }
    }

    /**
     * Finds a value in the array and return the corresponding key if successful.
     *
     * @param (Closure(TValue, TKey): bool)|mixed $value The value to search for, a Closure will find the first item that returns true.
     * @param bool $strict Whether to use strict comparison.
     *
     * @return array-key|null The key for `$value` if found, `null` otherwise.
     */
    public function findKey(mixed $value, bool $strict = false): int|string|null
    {
        if (! $value instanceof Closure) {
            $search = array_search($value, $this->array, $strict);

            return $search === false ? null : $search; // Keep empty values but convert false to null
        }

        return array_find_key($this->array, static fn ($item, $key) => $value($item, $key) === true);
    }

    /**
     * Chunks the array into chunks of the given size.
     *
     * @param int $size The size of each chunk.
     * @param bool $preserveKeys Whether to preserve the keys of the original array.
     *
     * @return self<array-key, self>
     */
    public function chunk(int $size, bool $preserveKeys = true): self
    {
        if ($size <= 0) {
            return new self();
        }

        $chunks = [];
        foreach (array_chunk($this->array, $size, $preserveKeys) as $chunk) {
            $chunks[] = new self($chunk);
        }

        return new self($chunks);
    }

    /**
     * Reduces the array to a single value using a callback.
     *
     * @template TReduceInitial
     * @template TReduceReturnType
     *
     * @param callable(TReduceInitial|TReduceReturnType, TValue, TKey): TReduceReturnType $callback
     * @param TReduceInitial $initial
     *
     * @return TReduceReturnType
     */
    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        $result = $initial;

        foreach ($this->array as $key => $value) {
            $result = $callback($result, $value, $key);
        }

        return $result;
    }

    /**
     * Gets a value from the array and remove it.
     *
     * @param array-key $key
     */
    public function pull(string|int $key, mixed $default = null): mixed
    {
        $value = $this->get($key, $default);

        $this->remove($key);

        return $value;
    }

    /**
     * Shuffles the array.
     *
     * @return self<TKey, TValue>
     */
    public function shuffle(): self
    {
        return new self(new Randomizer()->shuffleArray($this->array));
    }

    /**
     * @alias of `remove`.
     */
    public function forget(string|int|array $keys): self
    {
        return $this->remove($keys);
    }

    /**
     * Removes the specified items from the array.
     *
     * @param array-key|array<array-key> $keys The keys of the items to remove.
     *
     * @return self<TKey, TValue>
     */
    public function remove(string|int|array $keys): self
    {
        $keys = is_array($keys) ? $keys : [$keys];

        foreach ($keys as $key) {
            $this->offsetUnset($key);
        }

        return $this;
    }

    /**
     * Asserts whether the array is a list.
     * An array is a list if its keys consist of consecutive numbers.
     */
    public function isList(): bool
    {
        return array_is_list($this->array);
    }

    /**
     * Asserts whether the array is a associative.
     * An array is associative if its keys do not consist of consecutive numbers.
     */
    public function isAssoc(): bool
    {
        return ! $this->isList();
    }

    /**
     * Gets one or a specified number of random values from the array.
     *
     * @param int $number The number of random values to get.
     * @param bool $preserveKey Whether to include the keys of the original array.
     *
     * @return self<TKey, TValue>|mixed The random values, or a single value if `$number` is 1.
     */
    public function random(int $number = 1, bool $preserveKey = false): mixed
    {
        $count = count($this->array);

        if ($number > $count) {
            throw new InvalidArgumentException("Cannot retrieve {$number} items from an array of {$count} items.");
        }

        if ($number < 1) {
            throw new InvalidArgumentException("Random value only accepts positive integers, {$number} requested.");
        }

        $keys = new Randomizer()->pickArrayKeys($this->array, $number);

        $randomValues = [];
        foreach ($keys as $key) {
            $preserveKey
                ? $randomValues[$key] = $this->array[$key]
                : $randomValues[] = $this->array[$key];
        }

        if ($preserveKey === false) {
            shuffle($randomValues);
        }

        return count($randomValues) > 1
            ? new self($randomValues)
            : $randomValues[0];
    }

    /**
     * Retrieves values from a given key in each sub-array of the current array.
     * Optionally, you can pass a second parameter to also get the keys following the same pattern.
     *
     * @param string $value The key to assign the values from, support dot notation.
     * @param string|null $key The key to assign the keys from, support dot notation.
     *
     * @return self<TKey, TValue>
     */
    public function pluck(string $value, ?string $key = null): self
    {
        $results = [];

        foreach ($this->array as $item) {
            if (! is_array($item)) {
                continue;
            }

            $itemValue = arr($item)->get($value);

            /**
             * Perform basic pluck if no key is given.
             * Otherwise, also pluck the key as well.
             */
            if (is_null($key)) {
                $results[] = $itemValue;
            } else {
                $itemKey = arr($item)->get($key);
                $results[$itemKey] = $itemValue;
            }
        }

        return new self($results);
    }

    /**
     * Prepends the specified values to the instance.
     *
     * @param TValue $values
     */
    public function prepend(mixed ...$values): self
    {
        $array = $this->array;

        foreach (array_reverse($values) as $value) {
            $array = [$value, ...$array];
        }

        return new self($array);
    }

    /**
     * Appends the specified values to the instance.
     *
     * @param TValue $values
     */
    public function append(mixed ...$values): self
    {
        $array = $this->array;

        foreach ($values as $value) {
            $array = [...$array, $value];
        }

        return new self($array);
    }

    /**
     * @alias of `add`.
     */
    public function push(mixed $value): self
    {
        return $this->add($value);
    }

    /**
     * Appends the specified value to the array.
     *
     * @return self<TKey, TValue>
     */
    public function add(mixed $value): self
    {
        $this->array[] = $value;

        return $this;
    }

    /**
     * Pads the array to the specified size with a value.
     *
     * @return self<TKey, TValue>
     */
    public function pad(int $size, mixed $value): self
    {
        return new self(array_pad($this->array, $size, $value));
    }

    /**
     * Reverses the keys and values of the array.
     *
     * @return self<TValue&array-key, TKey>
     */
    public function flip(): self
    {
        return new self(array_flip($this->array));
    }

    /**
     * Returns a new instance with only unique items from the original array.
     *
     * @param string|null $key The key to use as the uniqueness criteria in nested arrays.
     * @param bool $shouldBeStrict Whether the comparison should be strict, only used when giving a key parameter.
     *
     * @return self<TKey, TValue>
     */
    public function unique(?string $key = null, bool $shouldBeStrict = false): self
    {
        if (is_null($key) && $shouldBeStrict === false) {
            return new self(array_unique($this->array, flags: SORT_REGULAR));
        }

        $uniqueItems = [];
        $uniqueFilteredValues = [];
        foreach ($this->array as $item) {
            // Ensure we don't check raw values with key filter
            if (! is_null($key) && ! is_array($item)) {
                continue;
            }

            $filterValue = is_array($item)
                ? arr($item)->get($key)
                : $item;

            if (is_null($filterValue)) {
                continue;
            }

            if (in_array($filterValue, $uniqueFilteredValues, strict: $shouldBeStrict)) {
                continue;
            }

            $uniqueItems[] = $item;
            $uniqueFilteredValues[] = $filterValue;
        }

        return new self($uniqueItems);
    }

    /**
     * Returns a new instance of the array with only the items that are not present in any of the given arrays.
     *
     * @param array<TKey, TValue>|self<TKey, TValue> ...$arrays
     *
     * @return self<TKey, TValue>
     */
    public function diff(array|self ...$arrays): self
    {
        $arrays = array_map(fn (array|self $array) => $array instanceof self ? $array->toArray() : $array, $arrays);

        return new self(array_diff($this->array, ...$arrays));
    }

    /**
     * Returns a new instance of the array with only the items whose keys are not present in any of the given arrays.
     *
     * @param array<TKey, TValue>|self<TKey, TValue> ...$arrays
     *
     * @return self<TKey, TValue>
     */
    public function diffKeys(array|self ...$arrays): self
    {
        $arrays = array_map(fn (array|self $array) => $array instanceof self ? $array->toArray() : $array, $arrays);

        return new self(array_diff_key($this->array, ...$arrays));
    }

    /**
     * Returns a new instance of the array with only the items that are present in all of the given arrays.
     *
     * @param array<TKey, TValue>|self<TKey, TValue> ...$arrays
     *
     * @return self<TKey, TValue>
     */
    public function intersect(array|self ...$arrays): self
    {
        $arrays = array_map(fn (array|self $array) => $array instanceof self ? $array->toArray() : $array, $arrays);

        return new self(array_intersect($this->array, ...$arrays));
    }

    /**
     * Returns a new instance of the array with only the items whose keys are present in all of the given arrays.
     *
     * @param array<TKey, TValue>|self<TKey, TValue> ...$arrays
     *
     * @return self<TKey, TValue>
     */
    public function intersectKeys(array|self ...$arrays): self
    {
        $arrays = array_map(fn (array|self $array) => $array instanceof self ? $array->toArray() : $array, $arrays);

        return new self(array_intersect_key($this->array, ...$arrays));
    }

    /**
     * Merges the array with the given arrays.
     *
     * @param array<TKey, TValue>|self<TKey, TValue> ...$arrays The arrays to merge.
     *
     * @return self<TKey, TValue>
     */
    public function merge(array|self ...$arrays): self
    {
        $arrays = array_map(fn (array|self $array) => $array instanceof self ? $array->toArray() : $array, $arrays);

        return new self(array_merge($this->array, ...$arrays));
    }

    /**
     * Creates a new array with this current array values as keys and the given values as values.
     *
     * @template TCombineValue
     *
     * @param array<array-key, TCombineValue>|self<array-key, TCombineValue> $values
     *
     * @return self<array-key, TCombineValue>
     */
    public function combine(array|self $values): self
    {
        $values = $values instanceof self
            ? $values->toArray()
            : $values;

        return new self(array_combine($this->array, $values));
    }

    /**
     * Creates an array from the specified `$string`, split by the given `$separator`.
     */
    public static function explode(string|Stringable $string, string $separator = ' '): self
    {
        if ($separator === '') {
            return new self([(string) $string]);
        }

        if ((string) $string === '') {
            return new self();
        }

        return new self(explode($separator, (string) $string));
    }

    /**
     * Asserts whether this instance is equal to the given array.
     */
    public function equals(array|self $other): bool
    {
        $other = is_array($other) ? $other : $other->array;

        return $this->array === $other;
    }

    /**
     * Returns the first item in the instance that matches the given `$filter`.
     * If `$filter` is `null`, returns the first item.
     *
     * @param null|Closure(TValue $value, TKey $key): bool $filter
     *
     * @return TValue
     */
    public function first(?Closure $filter = null): mixed
    {
        if ($this->array === []) {
            return null;
        }

        if ($filter === null) {
            return $this->array[array_key_first($this->array)];
        }

        return array_find($this->array, static fn ($value, $key) => $filter($value, $key));
    }

    /**
     * Returns the last item in the instance that matches the given `$filter`.
     * If `$filter` is `null`, returns the last item.
     *
     * @param null|Closure(TValue $value, TKey $key): bool $filter
     *
     * @return TValue
     */
    public function last(?Closure $filter = null): mixed
    {
        if ($this->array === []) {
            return null;
        }

        if ($filter === null) {
            return $this->array[array_key_last($this->array)];
        }

        return array_find($this->reverse()->toArray(), static fn ($value, $key) => $filter($value, $key));
    }

    /**
     * Returns an instance of the array without the last value.
     *
     * @param mixed $value The popped value will be stored in this variable
     */
    public function pop(mixed &$value = null): self
    {
        $value = $this->last();

        return new self(array_slice($this->array, 0, -1));
    }

    /**
     * Returns an instance of the array without the first value.
     *
     * @param mixed $value The unshifted value will be stored in this variable
     */
    public function unshift(mixed &$value = null): self
    {
        $value = $this->first();

        return new self(array_slice($this->array, 1));
    }

    /**
     * Returns a new instance of the array in reverse order.
     */
    public function reverse(): self
    {
        return new self(array_reverse($this->array));
    }

    /**
     * Asserts whether the array is empty.
     */
    public function isEmpty(): bool
    {
        return empty($this->array);
    }

    /**
     * Asserts whether the array is not empty.
     */
    public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }

    /**
     * Returns an instance of `StringHelper` with the values of the instance joined with the given `$glue`.
     */
    public function implode(string $glue): StringHelper
    {
        return str(implode($glue, $this->array));
    }

    /**
     * Returns a new instance with the keys of this array as values.
     *
     * @return self<array-key, TKey>
     */
    public function keys(): self
    {
        return new self(array_keys($this->array));
    }

    /**
     * Returns a new instance of this array without its keys.
     *
     * @return self<int, TValue>
     */
    public function values(): self
    {
        return new self(array_values($this->array));
    }

    /**
     * Returns a new instance of this array with only the items that pass the given `$filter`.
     * If `$filter` is `null`, the new instance will contain only values that are not `false` or `null`.
     *
     * @param null|Closure(mixed $value, mixed $key): bool $filter
     */
    public function filter(?Closure $filter = null): self
    {
        $array = [];
        $filter ??= static fn (mixed $value, mixed $_) => ! in_array($value, [false, null], strict: true);

        foreach ($this->array as $key => $value) {
            if ($filter($value, $key)) {
                $array[$key] = $value;
            }
        }

        return new self($array);
    }

    /**
     * Applies the given callback to all items of the instance.
     *
     * @param Closure(mixed $value, mixed $key): void $each
     */
    public function each(Closure $each): self
    {
        foreach ($this as $key => $value) {
            $each($value, $key);
        }

        return $this;
    }

    /**
     * Returns a new instance of the array, with each item transformed by the given callback.
     *
     * @template TMapValue
     *
     * @param  Closure(TValue, TKey): TMapValue $map
     *
     * @return static<TKey, TMapValue>
     */
    public function map(Closure $map): self
    {
        $array = [];

        foreach ($this->array as $key => $value) {
            $array[$key] = $map($value, $key);
        }

        return new self($array);
    }

    /**
     * Returns a new instance of the array, with each item transformed by the given callback.
     * The callback must return a generator, associating a key and a value.
     *
     * ### Example
     * ```php
     * arr(['a', 'b'])->mapWithKeys(fn (mixed $value, mixed $key) => yield $key => $value);
     * ```
     *
     * @param Closure(mixed $value, mixed $key): Generator $map
     */
    public function mapWithKeys(Closure $map): self
    {
        $array = [];

        foreach ($this->array as $key => $value) {
            $generator = $map($value, $key);

            // @phpstan-ignore instanceof.alwaysTrue
            if (! $generator instanceof Generator) {
                throw new InvalidMapWithKeysUsage();
            }

            $array[$generator->key()] = $generator->current();
        }

        return new self($array);
    }

    /**
     * Gets the value identified by the specified `$key`, or `$default` if no such value exists.
     *
     * @return mixed|ArrayHelper
     */
    public function get(int|string $key, mixed $default = null): mixed
    {
        $value = $this->array;

        if (isset($value[$key])) {
            return is_array($value[$key])
                ? new self($value[$key])
                : $value[$key];
        }

        $keys = is_int($key)
            ? [$key]
            : explode('.', $key);

        foreach ($keys as $key) {
            if (! isset($value[$key])) {
                return $default;
            }

            $value = $value[$key];
        }

        if (is_array($value)) {
            return new self($value);
        }

        return $value;
    }

    /**
     * Asserts whether a value identified by the specified `$key` exists.
     */
    public function has(int|string $key): bool
    {
        $array = $this->array;

        if (isset($array[$key])) {
            return true;
        }

        $keys = is_int($key)
            ? [$key]
            : explode('.', $key);

        foreach ($keys as $key) {
            if (! isset($array[$key])) {
                return false;
            }

            $array = &$array[$key];
        }

        return true;
    }

    /**
     * Asserts whether the instance contains an item that can be identified by `$search`.
     */
    public function contains(mixed $search): bool
    {
        return $this->first(fn (mixed $value) => $value === $search) !== null;
    }

    /**
     * Asserts whether all items in the instance pass the given `$callback`.
     *
     * @param Closure(TValue, TKey): bool $callback
     *
     * @return bool If the collection is empty, returns `true`.
     */
    public function every(?Closure $callback = null): bool
    {
        $callback ??= static fn (mixed $value) => ! is_null($value);

        return array_all($this->array, static fn (mixed $value, int|string $key) => $callback($value, $key));
    }

    /**
     * Associates the given `$value` to the given `$key` on the instance.
     */
    public function set(string $key, mixed $value): self
    {
        $array = $this->array;

        $current = &$array;

        $keys = explode('.', $key);

        foreach ($keys as $i => $key) {
            // If this is the last key in dot notation, we don't
            // need to go through the next steps.
            if (count($keys) === 1) {
                break;
            }

            // Remove the current key from our keys array
            // so that later we can use the first value
            // from that array as our key.
            unset($keys[$i]);

            // If we know this key is not an array, make it one.
            if (! isset($current[$key]) || ! is_array($current[$key])) {
                $current[$key] = [];
            }

            // Set the context to this key.
            $current = &$current[$key];
        }

        // Pull the first key out of the array
        // and use it to set the value.
        $current[array_shift($keys)] = $value;

        return new self($array);
    }

    /**
     * @alias of `set`
     */
    public function put(string $key, mixed $value): self
    {
        return $this->set($key, $value);
    }

    /**
     * Converts the dot-notated keys of the instance to a set of nested arrays.
     */
    public function unwrap(): self
    {
        $unwrapValue = function (string|int $key, mixed $value) {
            if (is_int($key)) {
                return [$key => $value];
            }

            $keys = explode('.', $key);

            for ($i = array_key_last($keys); $i >= 0; $i--) {
                $currentKey = $keys[$i];

                $value = [$currentKey => $value];
            }

            return $value;
        };

        $unwrapped = [];

        foreach ($this->array as $key => $value) {
            $unwrapped[] = $unwrapValue($key, $value);
        }

        $array = array_merge_recursive(...$unwrapped);

        return new self($array);
    }

    /**
     * Joins all values using the specified `$glue`. The last item of the string is separated by `$finalGlue`.
     */
    public function join(string $glue = ', ', ?string $finalGlue = ' and '): StringHelper
    {
        if ($finalGlue === '' || is_null($finalGlue)) {
            return $this->implode($glue);
        }

        if ($this->isEmpty()) {
            return str('');
        }

        $parts = $this->pop($last);

        if ($parts->isNotEmpty()) {
            return $parts->implode($glue)->append($finalGlue, $last);
        }

        return str($last);
    }

    /**
     * Flattens the instance to a single-level array, or until the specified `$depth` is reached.
     *
     * ### Example
     * ```php
     * arr(['foo', ['bar', 'baz']])->flatten(); // ['foo', 'bar', 'baz']
     * ```
     */
    public function flatten(int|float $depth = INF): self
    {
        $result = [];

        foreach ($this->array as $item) {
            if (! is_array($item)) {
                $result[] = $item;

                continue;
            }

            $values = $depth === 1
                ? array_values($item)
                : arr($item)->flatten($depth - 1);

            foreach ($values as $value) {
                $result[] = $value;
            }
        }

        return new self($result);
    }

    /**
     * Returns a new instance of the array, with each item transformed by the given callback, then flattens it by the specified depth.
     *
     * @template TMapValue
     *
     * @param  Closure(TValue, TKey): TMapValue[] $map
     *
     * @return static<TKey, TMapValue>
     */
    public function flatMap(Closure $map, int|float $depth = 1): self
    {
        return $this->map($map)->flatten($depth);
    }

    /**
     * Dumps the instance.
     */
    public function dump(mixed ...$dumps): self
    {
        lw($this->array, ...$dumps);

        return $this;
    }

    /**
     * Dumps the instance and stops the execution of the script.
     */
    public function dd(mixed ...$dd): void
    {
        ld($this->array, ...$dd);
    }

    /**
     * Returns the underlying array of the instance.
     *
     * @return array<TKey, TValue>
     */
    public function toArray(): array
    {
        return $this->array;
    }

    /**
     * Maps the items of the instance to the given object.
     *
     * @see Tempest\map()
     *
     * @template T
     * @param class-string<T> $to
     * @return self<T>
     */
    public function mapTo(string $to): self
    {
        return new self(map($this->array)->collection()->to($to));
    }

    /**
     * Returns a new instance of this array sorted by its values.
     *
     * @param bool $desc Sorts in descending order if `true`; defaults to `false` (ascending).
     * @param bool|null $preserveKeys Preserves array keys if `true`; reindexes numerically if `false`.
     *                                Defaults to `null`, which auto-detects preservation based on array type  (associative or list).
     * @param int $flags Sorting flags to define comparison behavior, defaulting to `SORT_REGULAR`.
     * @return self<array-key, TValue> Key type depends on whether array keys are preserved or not.
     */
    public function sort(bool $desc = false, ?bool $preserveKeys = null, int $flags = SORT_REGULAR): self
    {
        $array = $this->array;

        if ($preserveKeys === null) {
            $preserveKeys = $this->isAssoc();
        }

        if ($preserveKeys) {
            $desc ? arsort($array, $flags) : asort($array, $flags);
        } else {
            $desc ? rsort($array, $flags) : sort($array, $flags);
        }

        return new self($array);
    }

    /**
     * Returns a new instance of this array sorted by its values using a callback function.
     *
     * @param callable $callback The function to use for comparing values. It should accept two parameters
     *                           and return an integer less than, equal to, or greater than zero if the
     *                           first argument is considered to be respectively less than, equal to, or
     *                           greater than the second.
     * @param bool|null $preserveKeys Preserves array keys if `true`; reindexes numerically if `false`.
     *                                Defaults to `null`, which auto-detects preservation based on array type  (associative or list).
     * @return self<array-key, TValue> Key type depends on whether array keys are preserved or not.
     */
    public function sortByCallback(callable $callback, ?bool $preserveKeys = null): self
    {
        $array = $this->array;

        if ($preserveKeys === null) {
            $preserveKeys = $this->isAssoc();
        }

        $preserveKeys ? uasort($array, $callback) : usort($array, $callback);

        return new self($array);
    }

    /**
     * Returns a new instance of this array sorted by its keys.
     *
     * @param bool $desc Sorts in descending order if `true`; defaults to `false` (ascending).
     * @param int $flags Sorting flags to define comparison behavior, defaulting to `SORT_REGULAR`.
     * @return self<TKey, TValue>
     */
    public function sortKeys(bool $desc = false, int $flags = SORT_REGULAR): self
    {
        $array = $this->array;

        $desc ? krsort($array, $flags) : ksort($array, $flags);

        return new self($array);
    }

    /**
     * Returns a new instance of this array sorted by its keys using a callback function.
     *
     * @param callable $callback The function to use for comparing keys. It should accept two parameters
     *                           and return an integer less than, equal to, or greater than zero if the
     *                           first argument is considered to be respectively less than, equal to, or
     *                           greater than the second.
     * @return self<TKey, TValue>
     */
    public function sortKeysByCallback(callable $callback): self
    {
        $array = $this->array;

        uksort($array, $callback);

        return new self($array);
    }

    /**
     * Extracts a part of the instance.
     *
     * ### Example
     * ```php
     * arr([1, 2, 3, 4, 5])->slice(2); // [3, 4, 5]
     * ```
     */
    public function slice(int $offset, ?int $length = null): self
    {
        $length ??= $this->count() - $offset;

        return new self(array_slice($this->array, $offset, $length));
    }

    public static function wrap(mixed $input = []): array
    {
        return new self($input)->toArray();
    }
}
