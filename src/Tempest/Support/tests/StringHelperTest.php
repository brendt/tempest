<?php

declare(strict_types=1);

namespace Tempest\Support\Tests;

use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Tempest\Support\HtmlString;
use Tempest\Support\StringHelper;
use function Tempest\Support\arr;
use function Tempest\Support\str;

/**
 * @internal
 */
final class StringHelperTest extends TestCase
{
    public function test_title(): void
    {
        $this->assertTrue(str('jefferson costella')->title()->equals('Jefferson Costella'));
        $this->assertTrue(str('jefFErson coSTella')->title()->equals('Jefferson Costella'));

        $this->assertTrue(str()->title()->equals(''));
        $this->assertTrue(str('123 tempest')->title()->equals('123 Tempest'));
        $this->assertTrue(str('❤tempest')->title()->equals('❤Tempest'));
        $this->assertTrue(str('tempest ❤')->title()->equals('Tempest ❤'));
        $this->assertTrue(str('tempest123')->title()->equals('Tempest123'));
        $this->assertTrue(str('Tempest123')->title()->equals('Tempest123'));

        $longString = 'lorem ipsum ' . str_repeat('dolor sit amet ', 1000);
        $expectedResult = 'Lorem Ipsum Dolor Sit Amet ' . str_repeat('Dolor Sit Amet ', 999);

        $this->assertTrue(str($longString)->title()->equals($expectedResult));
    }

    public function test_deduplicate(): void
    {
        $this->assertTrue(str('/some//odd//path/')->deduplicate('/')->equals('/some/odd/path/'));
        $this->assertTrue(str(' tempest   php  framework ')->deduplicate()->equals(' tempest php framework '));
        $this->assertTrue(str('whaaat')->deduplicate('a')->equals('what'));
        $this->assertTrue(str('ムだだム')->deduplicate('だ')->equals('ムだム'));
    }

    public function test_pascal(): void
    {
        $this->assertTrue(str()->pascal()->equals(''));
        $this->assertTrue(str('foo bar')->pascal()->equals('FooBar'));
        $this->assertTrue(str('foo - bar')->pascal()->equals('FooBar'));
        $this->assertTrue(str('foo__bar')->pascal()->equals('FooBar'));
        $this->assertTrue(str('_foo__bar')->pascal()->equals('FooBar'));
        $this->assertTrue(str('-foo__bar')->pascal()->equals('FooBar'));
        $this->assertTrue(str('fooBar')->pascal()->equals('FooBar'));
        $this->assertTrue(str('foo_bar')->pascal()->equals('FooBar'));
        $this->assertTrue(str('foo_bar1')->pascal()->equals('FooBar1'));
        $this->assertTrue(str('1foo_bar')->pascal()->equals('1fooBar'));
        $this->assertTrue(str('1foo_bar11')->pascal()->equals('1fooBar11'));
        $this->assertTrue(str('1foo_1bar1')->pascal()->equals('1foo1bar1'));
        $this->assertTrue(str('foo-barBaz')->pascal()->equals('FooBarBaz'));
        $this->assertTrue(str('foo-bar_baz')->pascal()->equals('FooBarBaz'));
        // TODO: support when `mb_ucfirst` has landed in PHP 8.4
        // $thisTrueaertSame('ÖffentlicheÜberraschungen', str('öffentliche-überraschungen')->pascal()->$this->equals());
    }

    public function test_kebab(): void
    {
        $this->assertTrue(str()->kebab()->equals(''));
        $this->assertTrue(str('foo bar')->kebab()->equals('foo-bar'));
        $this->assertTrue(str('foo - bar')->kebab()->equals('foo-bar'));
        $this->assertTrue(str('foo__bar')->kebab()->equals('foo-bar'));
        $this->assertTrue(str('_foo__bar')->kebab()->equals('foo-bar'));
        $this->assertTrue(str('-foo__bar')->kebab()->equals('foo-bar'));
        $this->assertTrue(str('fooBar')->kebab()->equals('foo-bar'));
        $this->assertTrue(str('foo_bar')->kebab()->equals('foo-bar'));
        $this->assertTrue(str('foo_bar1')->kebab()->equals('foo-bar1'));
        $this->assertTrue(str('1foo_bar')->kebab()->equals('1foo-bar'));
        $this->assertTrue(str('1foo_bar11')->kebab()->equals('1foo-bar11'));
        $this->assertTrue(str('1foo_1bar1')->kebab()->equals('1foo-1bar1'));
        $this->assertTrue(str('foo-barBaz')->kebab()->equals('foo-bar-baz'));
        $this->assertTrue(str('foo-bar_baz')->kebab()->equals('foo-bar-baz'));
    }

    public function test_snake(): void
    {
        $this->assertTrue(str('')->snake()->equals(''));
        $this->assertTrue(str('foo bar')->snake()->equals('foo_bar'));
        $this->assertTrue(str('foo - bar')->snake()->equals('foo_bar'));
        $this->assertTrue(str('foo__bar')->snake()->equals('foo_bar'));
        $this->assertTrue(str('_foo__bar')->snake()->equals('foo_bar'));
        $this->assertTrue(str('-foo__bar')->snake()->equals('foo_bar'));
        $this->assertTrue(str('fooBar')->snake()->equals('foo_bar'));
        $this->assertTrue(str('foo_bar')->snake()->equals('foo_bar'));
        $this->assertTrue(str('foo_bar1')->snake()->equals('foo_bar1'));
        $this->assertTrue(str('1foo_bar')->snake()->equals('1foo_bar'));
        $this->assertTrue(str('1foo_bar11')->snake()->equals('1foo_bar11'));
        $this->assertTrue(str('1foo_1bar1')->snake()->equals('1foo_1bar1'));
        $this->assertTrue(str('foo-barBaz')->snake()->equals('foo_bar_baz'));
        $this->assertTrue(str('foo-bar_baz')->snake()->equals('foo_bar_baz'));
    }

    #[TestWith([0])]
    #[TestWith([16])]
    #[TestWith([100])]
    public function test_random(int $length): void
    {
        $this->assertEquals($length, str()->random($length)->length());
    }

    public function test_finish(): void
    {
        $this->assertTrue(str('foo')->finish('/')->equals('foo/'));
        $this->assertTrue(str('foo/')->finish('/')->equals('foo/'));
        $this->assertTrue(str('abbcbc')->finish('bc')->equals('abbc'));
        $this->assertTrue(str('abcbbcbc')->finish('bc')->equals('abcbbc'));
    }

    public function test_str_after(): void
    {
        $this->assertTrue(str('hannah')->after('han')->equals('nah'));
        $this->assertTrue(str('hannah')->after(str('han'))->equals('nah'));
        $this->assertTrue(str('hannah')->after('n')->equals('nah'));
        $this->assertTrue(str('ééé hannah')->after('han')->equals('nah'));
        $this->assertTrue(str('hannah')->after('xxxx')->equals('hannah'));
        $this->assertTrue(str('hannah')->after('')->equals('hannah'));
        $this->assertTrue(str('han0nah')->after('0')->equals('nah'));
        $this->assertTrue(str('han2nah')->after('2')->equals('nah'));
        $this->assertTrue(str('@foo@bar.com')->after(['@', '.'])->equals('foo@bar.com'));
        $this->assertTrue(str('foo@bar.com')->after(['@', '.'])->equals('bar.com'));
        $this->assertTrue(str('foobar.com')->after(['@', '.'])->equals('com'));
    }

    public function test_str_after_last(): void
    {
        $this->assertTrue(str('yvette')->afterLast('yve')->equals('tte'));
        $this->assertTrue(str('yvette')->afterLast(str('yve'))->equals('tte'));
        $this->assertTrue(str('yvette')->afterLast('t')->equals('e'));
        $this->assertTrue(str('ééé yvette')->afterLast('t')->equals('e'));
        $this->assertTrue(str('yvette')->afterLast('tte')->equals(''));
        $this->assertTrue(str('yvette')->afterLast('xxxx')->equals('yvette'));
        $this->assertTrue(str('yvette')->afterLast('')->equals('yvette'));
        $this->assertTrue(str('yv0et0te')->afterLast('0')->equals('te'));
        $this->assertTrue(str('yv2et2te')->afterLast('2')->equals('te'));
        $this->assertTrue(str('----foo')->afterLast('---')->equals('foo'));
        $this->assertTrue(str('@foo@bar.com')->afterLast(['@', '.'])->equals('com'));
    }

    public function test_str_between(): void
    {
        $this->assertTrue(str('abc')->between('', 'c')->equals('abc'));
        $this->assertTrue(str('abc')->between('a', '')->equals('abc'));
        $this->assertTrue(str('abc')->between('', '')->equals('abc'));
        $this->assertTrue(str('abc')->between('a', 'c')->equals('b'));
        $this->assertTrue(str('abc')->between(str('a'), str('c'))->equals('b'));
        $this->assertTrue(str('dddabc')->between('a', 'c')->equals('b'));
        $this->assertTrue(str('abcddd')->between('a', 'c')->equals('b'));
        $this->assertTrue(str('dddabcddd')->between('a', 'c')->equals('b'));
        $this->assertTrue(str('hannah')->between('ha', 'ah')->equals('nn'));
        $this->assertTrue(str('[a]ab[b]')->between('[', ']')->equals('a]ab[b'));
        $this->assertTrue(str('foofoobar')->between('foo', 'bar')->equals('foo'));
        $this->assertTrue(str('foobarbar')->between('foo', 'bar')->equals('bar'));
        $this->assertTrue(str('12345')->between('1', '5')->equals('234'));
        $this->assertTrue(str('123456789')->between('123', '6789')->equals('45'));
        $this->assertTrue(str('nothing')->between('foo', 'bar')->equals('nothing'));
    }

    public function test_str_before(): void
    {
        $this->assertTrue(str('hannah')->before('nah')->equals('han'));
        $this->assertTrue(str('hannah')->before(str('nah'))->equals('han'));
        $this->assertTrue(str('hannah')->before('n')->equals('ha'));
        $this->assertTrue(str('ééé hannah')->before('han')->equals('ééé '));
        $this->assertTrue(str('hannah')->before('xxxx')->equals('hannah'));
        $this->assertTrue(str('hannah')->before('')->equals('hannah'));
        $this->assertTrue(str('han0nah')->before('0')->equals('han'));
        $this->assertTrue(str('han2nah')->before('2')->equals('han'));
        $this->assertTrue(str('')->before('')->equals(''));
        $this->assertTrue(str('')->before('a')->equals(''));
        $this->assertTrue(str('a')->before('a')->equals(''));
        $this->assertTrue(str('foo@bar.com')->before('@')->equals('foo'));
        $this->assertTrue(str('foo@@bar.com')->before('@')->equals('foo'));
        $this->assertTrue(str('@foo@bar.com')->before('@')->equals(''));
        $this->assertTrue(str('foo@bar.com')->before(['@', '.'])->equals('foo'));
        $this->assertTrue(str('@foo@bar.com')->before(['@', '.'])->equals(''));
    }

    public function test_str_before_last(): void
    {
        $this->assertTrue(str('yvette')->beforeLast('tte')->equals('yve'));
        $this->assertTrue(str('yvette')->beforeLast(str('tte'))->equals('yve'));
        $this->assertTrue(str('yvette')->beforeLast('t')->equals('yvet'));
        $this->assertTrue(str('ééé yvette')->beforeLast('yve')->equals('ééé '));
        $this->assertTrue(str('yvette')->beforeLast('yve')->equals(''));
        $this->assertTrue(str('yvette')->beforeLast('xxxx')->equals('yvette'));
        $this->assertTrue(str('yvette')->beforeLast('')->equals('yvette'));
        $this->assertTrue(str('yv0et0te')->beforeLast('0')->equals('yv0et'));
        $this->assertTrue(str('yv2et2te')->beforeLast('2')->equals('yv2et'));
        $this->assertTrue(str('')->beforeLast('test')->equals(''));
        $this->assertTrue(str('yvette')->beforeLast('yvette')->equals(''));
        $this->assertTrue(str('tempest framework')->beforeLast(' ')->equals('tempest'));
        $this->assertTrue(str("yvette\tyv0et0te")->beforeLast("\t")->equals('yvette'));
        $this->assertTrue(str('This is Tempest.')->beforeLast([' ', '.'])->equals('This is Tempest'));
        $this->assertTrue(str('This is Tempest')->beforeLast([' ', '.'])->equals('This is'));
    }

    public function test_starts_with(): void
    {
        $this->assertTrue(str('abc')->startsWith('a'));
        $this->assertTrue(str('abc')->startsWith((str('a'))));
        $this->assertFalse(str('abc')->startsWith('c'));
    }

    public function test_ends_with(): void
    {
        $this->assertTrue(str('abc')->endsWith('c'));
        $this->assertTrue(str('abc')->endsWith(str('c')));
        $this->assertFalse(str('abc')->endsWith('a'));
    }

    public function test_replace(): void
    {
        $this->assertTrue(str('foo bar')->replace('bar', 'baz')->equals('foo baz'));
        $this->assertTrue(str('foo bar')->replace(str('bar'), 'baz')->equals('foo baz'));
        $this->assertTrue(str('foo bar')->replace('bar', str('baz'))->equals('foo baz'));
        $this->assertTrue(str('jon doe')->replace(['jon', 'jane'], 'luke')->equals('luke doe'));
        $this->assertTrue(str('jon doe')->replace(['jon', 'jane', 'doe'], ['Jon', 'Jane', 'Doe'])->equals('Jon Doe'));
        $this->assertTrue(
            str('jon doe')->replace(['jon', 'jane', 'doe'], '<censored>')->equals('<censored> <censored>'),
        );
    }

    public function test_replace_last(): void
    {
        $this->assertTrue(str('foobar foobar')->replaceLast('bar', 'qux')->equals('foobar fooqux'));
        $this->assertTrue(str('foo/bar? foo/bar?')->replaceLast('bar?', 'qux?')->equals('foo/bar? foo/qux?'));
        $this->assertTrue(str('foobar foobar')->replaceLast('bar', '')->equals('foobar foo'));
        $this->assertTrue(str('foobar foobar')->replaceLast('xxx', 'yyy')->equals('foobar foobar'));
        $this->assertTrue(str('foobar foobar')->replaceLast('', 'yyy')->equals('foobar foobar'));
        $this->assertTrue(str('Malmö Jönköping')->replaceLast('ö', 'xxx')->equals('Malmö Jönkxxxping'));
        $this->assertTrue(str('Malmö Jönköping')->replaceLast('', 'yyy')->equals('Malmö Jönköping'));
    }

    public function test_replace_first(): void
    {
        $this->assertTrue(str('foobar foobar')->replaceFirst('bar', 'qux')->equals('fooqux foobar'));
        $this->assertTrue(str('foo/bar? foo/bar?')->replaceFirst('bar?', 'qux?')->equals('foo/qux? foo/bar?'));
        $this->assertTrue(str('foobar foobar')->replaceFirst('bar', '')->equals('foo foobar'));
        $this->assertTrue(str('foobar foobar')->replaceFirst('xxx', 'yyy')->equals('foobar foobar'));
        $this->assertTrue(str('foobar foobar')->replaceFirst('', 'yyy')->equals('foobar foobar'));
        $this->assertTrue(str('Jönköping Malmö')->replaceFirst('ö', 'xxx')->equals('Jxxxnköping Malmö'));
        $this->assertTrue(str('Jönköping Malmö')->replaceFirst('', 'yyy')->equals('Jönköping Malmö'));
    }

    public function test_replace_end(): void
    {
        $this->assertTrue(str('foobar fooqux')->replaceEnd('bar', 'qux')->equals('foobar fooqux'));
        $this->assertTrue(str('foo/bar? foo/qux?')->replaceEnd('bar?', 'qux?')->equals('foo/bar? foo/qux?'));
        $this->assertTrue(str('foobar foo')->replaceEnd('bar', '')->equals('foobar foo'));
        $this->assertTrue(str('foobar foobar')->replaceEnd('xxx', 'yyy')->equals('foobar foobar'));
        $this->assertTrue(str('foobar foobar')->replaceEnd('', 'yyy')->equals('foobar foobar'));
        $this->assertTrue(str('fooxxx foobar')->replaceEnd('xxx', 'yyy')->equals('fooxxx foobar'));
        $this->assertTrue(str('Malmö Jönköping')->replaceEnd('ö', 'xxx')->equals('Malmö Jönköping'));
        $this->assertTrue(str('Malmö Jönköping')->replaceEnd('öping', 'yyy')->equals('Malmö Jönkyyy'));
    }

    public function test_append(): void
    {
        $this->assertTrue(str('foo')->append('bar')->equals('foobar'));
        $this->assertTrue(str('foo')->append('bar', 'baz')->equals('foobarbaz'));
        $this->assertTrue(str('foo')->append(str('bar'), str('baz'))->equals('foobarbaz'));
    }

    public function test_prepend(): void
    {
        $this->assertTrue(str('bar')->prepend('foo')->equals('foobar'));
        $this->assertTrue(str('baz')->prepend('bar', 'foo')->equals('barfoobaz'));
        $this->assertTrue(str('baz')->prepend(str('bar'), str('foo'))->equals('barfoobaz'));
    }

    public function test_match(): void
    {
        $match = str('10-abc')->match('/(?<id>\d+-)/')['id'];

        $this->assertSame('10-', $match);
    }

    public function test_matches(): void
    {
        $this->assertTrue(str('10-abc')->matches('/(?<id>\d+-)/'));
        $this->assertTrue(str('10-abc')->matches('/(\d+-)/'));
        $this->assertTrue(str('10-abc')->matches('/\d+-/'));
        $this->assertFalse(str('10abc')->matches('/\d+-/'));
        $this->assertFalse(str('abc')->matches('/\d+-/'));
    }

    public function test_replace_regex(): void
    {
        $this->assertTrue(str('10-abc')->replaceRegex('/(?<id>\d+-)/', '')->equals('abc'));
        $this->assertTrue(str('10-abc')->replaceRegex('/(?<id>\d+-)/', fn () => '')->equals('abc'));
        $this->assertTrue(str('10-abc')->replaceRegex(['/\d/', '/\w/'], ['#', 'X'])->equals('##-XXX'));
    }

    public function test_match_all(): void
    {
        // Test for Simple Pattern
        $regex = '/Hello/';
        $matches = str('Hello world, Hello universe')->matchAll($regex);
        $expected = [['Hello', 'Hello']];
        $this->assertSame($expected, $matches);

        // Test for Named Capture Groups
        $regex = '/(?<adjective>quick|lazy) (?<noun>brown|dog)/';
        $matches = str('The quick brown fox, then the lazy dog')->matchAll($regex);
        $expectedAdjectives = [
            [
                'quick brown',
                'lazy dog',
            ],
            'adjective' => [
                'quick',
                'lazy',
            ],
            1 => [
                'quick',
                'lazy',
            ],
            'noun' => [
                'brown',
                'dog',
            ],
            2 => [
                'brown',
                'dog',
            ],
        ];

        $this->assertSame($expectedAdjectives, $matches);

        // Test for No Matches
        $regex = '/cat/';
        $matches = str('The quick brown fox, then the lazy dog')->matchAll($regex);
        $expected = [];
        $this->assertSame($expected, $matches);

        // Test for Mixed Captures
        $regex = '/(?<adjective>quick|lazy) (?<noun>brown|dog) (?<action>jumps|eats)?/';
        $matches = str('The quick brown fox, then the lazy dog eats')->matchAll($regex);
        $expected = [
            [
                'quick brown ',
                'lazy dog eats',
            ],
            'adjective' => [
                'quick',
                'lazy',
            ],
            [
                'quick',
                'lazy',
            ],
            'noun' => [
                'brown',
                'dog',
            ],
            [
                'brown',
                'dog',
            ],
            'action' => [
                '',
                'eats',
            ],
            [
                '',
                'eats',
            ],
        ];
        $this->assertSame($expected, $matches);

        // Test flags
        $regex = '/(foo)(bar)/';
        $matches = str('foobarbaz')->matchAll($regex, PREG_OFFSET_CAPTURE);
        $expected = [
            [
                [
                    'foobar',
                    0,
                ],
            ],
            [
                [
                    'foo',
                    0,
                ],
            ],
            [
                [
                    'bar',
                    3,
                ],
            ],
        ];
        $this->assertSame($expected, $matches);

        $regex = '/^def/';
        $matches = str('abcdef')->matchAll(regex: $regex, offset: 3);
        $expected = [];
        $this->assertSame($expected, $matches);
    }

    public function test_explode(): void
    {
        $this->assertSame(['path', 'to', 'tempest'], str('path/to/tempest')->explode('/')->toArray());
        $this->assertSame(['john', 'doe'], str('john doe')->explode()->toArray());
    }

    public function test_implode(): void
    {
        $this->assertSame('path/to/tempest', StringHelper::implode(['path', 'to', 'tempest'], '/')->toString());
        $this->assertSame('john doe', StringHelper::implode(['john', 'doe'])->toString());
        $this->assertSame('path/to/tempest', StringHelper::implode(arr(['path', 'to', 'tempest']), '/')->toString());
        $this->assertSame('john doe', StringHelper::implode(arr(['john', 'doe']))->toString());
    }

    #[TestWith([['Jon', 'Jane'], 'Jon and Jane'])]
    #[TestWith([['Jon', 'Jane', 'Jill'], 'Jon, Jane and Jill'])]
    public function test_join(array $initial, string $expected): void
    {
        $this->assertEquals($expected, StringHelper::join($initial));
    }

    #[TestWith([['Jon', 'Jane'], ', ', ' and maybe ', 'Jon and maybe Jane'])]
    #[TestWith([['Jon', 'Jane', 'Jill'], ' + ', ' and ', 'Jon + Jane and Jill'])]
    #[TestWith([['Jon', 'Jane', 'Jill'], ' + ', null, 'Jon + Jane + Jill'])]
    public function test_join_with_glues(array $initial, string $glue, ?string $finalGlue, string $expected): void
    {
        $this->assertTrue(StringHelper::join($initial, $glue, $finalGlue)->equals($expected));
    }

    public function test_excerpt(): void
    {
        $content = str('a
b
c
d
e
f
g');

        $this->assertTrue($content->excerpt(2, 4)->equals('b
c
d'));

        $this->assertTrue($content->excerpt(-10, 2)->equals('a
b'));

        $this->assertTrue($content->excerpt(7, 100)->equals('g'));

        $this->assertSame([2 => 'b', 3 => 'c', 4 => 'd'], $content->excerpt(2, 4, asArray: true)->toArray());
    }

    public function test_wrap(): void
    {
        $this->assertSame('Leon Scott Kennedy', str('Scott')->wrap(before: 'Leon ', after: ' Kennedy')->toString());
        $this->assertSame('"value"', str('value')->wrap('"')->toString());
    }

    public function test_unwrap(): void
    {
        $this->assertSame('Scott', str('Leon Scott Kennedy')->unwrap(before: 'Leon ', after: ' Kennedy')->toString());
        $this->assertSame('value', str('"value"')->unwrap('"')->toString());
        $this->assertSame('"value"', str('"value"')->unwrap('`')->toString());
        $this->assertSame('[value', str('[value')->unwrap('[', ']')->toString());
        $this->assertEquals('some: "json"', str('{some: "json"}')->unwrap('{', '}')->toString());

        $this->assertSame('value', str('[value')->unwrap('[', ']', strict: false)->toString());
        $this->assertSame('value', str('value]')->unwrap('[', ']', strict: false)->toString());
        $this->assertSame('Scott', str('Scott Kennedy')->unwrap(before: 'Leon ', after: ' Kennedy', strict: false)->toString());
    }

    public function test_start(): void
    {
        $this->assertSame('Leon Scott Kennedy', str('Scott Kennedy')->start('Leon ')->toString());
        $this->assertSame('Leon Scott Kennedy', str('Leon Scott Kennedy')->start('Leon ')->toString());
    }

    public function test_limit(): void
    {
        $this->assertSame('Lorem', str('Lorem ipsum')->truncate(5)->toString());
        $this->assertSame('Lorem...', str('Lorem ipsum')->truncate(5, end: '...')->toString());
        $this->assertSame('...', str('Lorem ipsum')->truncate(0, end: '...')->toString());
        $this->assertSame('L...', str('Lorem ipsum')->truncate(1, end: '...')->toString());
        $this->assertSame('Lorem ipsum', str('Lorem ipsum')->truncate(100)->toString());
        $this->assertSame('Lorem ipsum', str('Lorem ipsum')->truncate(100, end: '...')->toString());
    }

    public function test_substr(): void
    {
        $this->assertSame('Lorem', str('Lorem ipsum')->substr(0, length: 5)->toString());
        $this->assertSame('ipsum', str('Lorem ipsum')->substr(6, length: 5)->toString());
        $this->assertSame('ipsum', str('Lorem ipsum')->substr(6)->toString());
        $this->assertSame('ipsum', str('Lorem ipsum')->substr(-5)->toString());
        $this->assertSame('ipsum', str('Lorem ipsum')->substr(-5, length: 5)->toString());
    }

    public function test_take(): void
    {
        // positive
        $this->assertSame('Lorem', str('Lorem ipsum')->take(5)->toString());
        $this->assertSame('Lorem ipsum', str('Lorem ipsum')->take(100)->toString());

        // negative
        $this->assertSame('ipsum', str('Lorem ipsum')->take(-5)->toString());
    }

    public function test_split(): void
    {
        $this->assertSame([PHP_EOL], str(PHP_EOL)->split(100)->toArray());
        $this->assertSame([''], str('')->split(1)->toArray());
        $this->assertSame([], str('123')->split(-1)->toArray());
        $this->assertSame(['1', '2', '3'], str('123')->split(1)->toArray());
        $this->assertSame(['123'], str('123')->split(1000)->toArray());
        $this->assertSame(['foo', 'bar', 'baz'], str('foobarbaz')->split(3)->toArray());
        $this->assertSame(['foo', 'bar', 'baz', '22'], str('foobarbaz22')->split(3)->toArray());
    }

    public function test_insert_at(): void
    {
        $this->assertSame('foo', str()->insertAt(0, 'foo')->toString());
        $this->assertSame('foo', str()->insertAt(-1, 'foo')->toString());
        $this->assertSame('foo', str()->insertAt(100, 'foo')->toString());
        $this->assertSame('foo', str()->insertAt(-100, 'foo')->toString());
        $this->assertSame('foobar', str('bar')->insertAt(0, 'foo')->toString());
        $this->assertSame('barfoo', str('bar')->insertAt(3, 'foo')->toString());
        $this->assertSame('foobarbaz', str('foobaz')->insertAt(3, 'bar')->toString());
        $this->assertSame('123', str('13')->insertAt(-1, '2')->toString());
    }

    public function test_replace_at(): void
    {
        $this->assertSame('foobar', str('foo2bar')->replaceAt(4, -1, '')->toString());
        $this->assertSame('foobar', str('foo2bar')->replaceAt(3, 1, '')->toString());
        $this->assertSame('fooquxbar', str('foo2bar')->replaceAt(3, 1, 'qux')->toString());
        $this->assertSame('foobarbaz', str('barbaz')->replaceAt(0, 0, 'foo')->toString());
        $this->assertSame('barbazfoo', str('barbaz')->replaceAt(6, 0, 'foo')->toString());
        $this->assertSame('bar', str('foo')->replaceAt(0, 3, 'bar')->toString());
        $this->assertSame('abc1', str('abcd')->replaceAt(-1, 1, '1')->toString());
        $this->assertSame('ab1d', str('abcd')->replaceAt(-1, -1, '1')->toString());
        $this->assertSame('abc', str('abc')->replaceAt(3, 1, '')->toString());
    }

    public function test_strip_tags(): void
    {
        $this->assertSame('Hello World', str('<p>Hello World</p>')->stripTags()->toString());
        $this->assertSame('Hello World', str('<p>Hello <strong>World</strong></p>')->stripTags()->toString());
        $this->assertSame('Hello <strong>World</strong>', str('<p>Hello <strong>World</strong></p>')->stripTags(allowed: '<strong>')->toString());
        $this->assertSame('<p>Hello World</p>', str('<p>Hello <strong>World</strong></p>')->stripTags(allowed: '<p>')->toString());

        $this->assertSame('Hello <strong>World</strong>', str('<p>Hello <strong>World</strong></p>')->stripTags(allowed: 'strong')->toString());
        $this->assertSame('<p>Hello World</p>', str('<p>Hello <strong>World</strong></p>')->stripTags(allowed: 'p')->toString());
    }

    public function test_when(): void
    {
        $this->assertTrue(str('foo')->when(true, fn ($s) => $s->append('bar'))->equals('foobar'));
        $this->assertTrue(str('foo')->when(false, fn ($s) => $s->append('bar'))->equals('foo'));

        $this->assertTrue(str('foo')->when(fn () => true, fn ($s) => $s->append('bar'))->equals('foobar'));
        $this->assertTrue(str('foo')->when(fn () => false, fn ($s) => $s->append('bar'))->equals('foo'));

        $this->assertTrue(str('foo')->when(fn ($s) => $s->startsWith('foo'), fn ($s) => $s->append('bar'))->equals('foobar'));
        $this->assertTrue(str('foo')->when(fn ($s) => $s->startsWith('bar'), fn ($s) => $s->append('bar'))->equals('foo'));

        $this->assertTrue(str('foo')->when(true, fn ($s) => $s->append('bar'))->equals('foobar'));
        $this->assertTrue(str('foo')->when(false, fn ($s) => $s->append('bar'))->equals('foo'));
    }

    public function test_align_center(): void
    {
        $this->assertSame('  foo  ', str('foo')->alignCenter(7)->toString());
        $this->assertSame('  foo  ', str(' foo ')->alignCenter(7)->toString());
        $this->assertSame('   foo    ', str('foo')->alignCenter(10)->toString());

        $this->assertSame('  foo  ', str('foo')->alignCenter(2, padding: 2)->toString());
        $this->assertSame('   foo    ', str('foo')->alignCenter(10, padding: 2)->toString());
        $this->assertSame('  foo  ', str(' foo ')->alignCenter(2, padding: 2)->toString());
    }

    public function test_align_right(): void
    {
        $this->assertSame('foo', str('foo')->alignRight(3)->toString());
        $this->assertSame('       foo', str('foo')->alignRight(10)->toString());
        $this->assertSame('       foo', str(' foo')->alignRight(10)->toString());
        $this->assertSame('     foo  ', str(' foo')->alignRight(10, padding: 2)->toString());
        $this->assertSame('  foo  ', str('foo')->alignRight(2, padding: 2)->toString());
    }

    public function test_align_left(): void
    {
        $this->assertSame('foo', str('foo')->alignLeft(3)->toString());
        $this->assertSame('foo       ', str('foo')->alignLeft(10)->toString());
        $this->assertSame('foo       ', str(' foo')->alignLeft(10)->toString());
        $this->assertSame('  foo     ', str(' foo')->alignLeft(10, padding: 2)->toString());
        $this->assertSame('  foo  ', str('foo')->alignLeft(2, padding: 2)->toString());
    }

    public function test_to_html_string(): void
    {
        $this->assertInstanceOf(HtmlString::class, str('foo')->toHtmlString());
        $this->assertSame('foo', (string) str('foo')->toHtmlString());
    }

    public function test_contains(): void
    {
        $this->assertTrue(str('foo')->contains('fo'));
        $this->assertFalse(str('foo')->contains('bar'));
    }

    public function test_levenshtein(): void
    {
        $this->assertSame(0, str('foo')->levenshtein('foo'));
        $this->assertSame(3, str('foo')->levenshtein('bar'));
    }

    public function test_is_empty(): void
    {
        $this->assertTrue(str('')->isEmpty());
        $this->assertFalse(str('a')->isEmpty());
    }

    public function test_is_not_empty(): void
    {
        $this->assertTrue(str('a')->isNotEmpty());
        $this->assertFalse(str('')->isNotEmpty());
    }

    public function test_reverse(): void
    {
        $this->assertSame('oof', str('foo')->reverse()->toString());
        $this->assertSame('…oof', str('foo…')->reverse()->toString());
    }

    public function test_truncate_start(): void
    {
        $this->assertSame('Lorem ipsum', str('Lorem ipsum')->truncateStart(20, start: '…')->toString());
        $this->assertSame('…ipsum', str('Lorem ipsum')->truncateStart(5, start: '…')->toString());
        $this->assertSame('…', str('Lorem ipsum')->truncateStart(0, start: '…')->toString());
        $this->assertSame('…orem ipsum', str('Lorem ipsum')->truncateStart(-1, start: '…')->toString());
    }
}
