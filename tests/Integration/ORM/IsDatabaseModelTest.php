<?php

declare(strict_types=1);

namespace Tests\Tempest\Integration\ORM;

use Carbon\Carbon;
use Tempest\Database\Exceptions\MissingRelation;
use Tempest\Database\Exceptions\MissingValue;
use Tempest\Database\Id;
use Tempest\Database\Migrations\CreateMigrationsTable;
use Tests\Tempest\Fixtures\Migrations\CreateAuthorTable;
use Tests\Tempest\Fixtures\Migrations\CreateBookTable;
use Tests\Tempest\Fixtures\Models\A;
use Tests\Tempest\Fixtures\Models\AWithEager;
use Tests\Tempest\Fixtures\Models\AWithLazy;
use Tests\Tempest\Fixtures\Models\AWithValue;
use Tests\Tempest\Fixtures\Models\B;
use Tests\Tempest\Fixtures\Models\C;
use Tests\Tempest\Fixtures\Modules\Books\Models\Author;
use Tests\Tempest\Fixtures\Modules\Books\Models\AuthorType;
use Tests\Tempest\Fixtures\Modules\Books\Models\Book;
use Tests\Tempest\Integration\FrameworkIntegrationTestCase;
use Tests\Tempest\Integration\ORM\Migrations\CreateATable;
use Tests\Tempest\Integration\ORM\Migrations\CreateBTable;
use Tests\Tempest\Integration\ORM\Migrations\CreateCarbonModelTable;
use Tests\Tempest\Integration\ORM\Migrations\CreateCTable;
use Tests\Tempest\Integration\ORM\Migrations\CreateHasManyChildTable;
use Tests\Tempest\Integration\ORM\Migrations\CreateHasManyParentTable;
use Tests\Tempest\Integration\ORM\Migrations\CreateHasManyThroughTable;
use Tests\Tempest\Integration\ORM\Models\CarbonModel;
use Tests\Tempest\Integration\ORM\Models\ChildModel;
use Tests\Tempest\Integration\ORM\Models\ParentModel;
use Tests\Tempest\Integration\ORM\Models\ThroughModel;
use function Tempest\map;

/**
 * @internal
 */
final class IsDatabaseModelTest extends FrameworkIntegrationTestCase
{
    public function test_create_and_update_model(): void
    {
        $this->migrate(
            CreateMigrationsTable::class,
            FooDatabaseMigration::class,
        );

        $foo = Foo::create(
            bar: 'baz',
        );

        $this->assertSame('baz', $foo->bar);
        $this->assertInstanceOf(Id::class, $foo->id);

        $foo = Foo::find($foo->id);

        $this->assertSame('baz', $foo->bar);
        $this->assertInstanceOf(Id::class, $foo->id);

        $foo->update(
            bar: 'boo',
        );

        $foo = Foo::find($foo->id);

        $this->assertSame('boo', $foo->bar);
    }

    public function test_creating_many_and_saving_preserves_model_id(): void
    {
        $this->migrate(
            CreateMigrationsTable::class,
            FooDatabaseMigration::class,
        );

        $a = Foo::create(
            bar: 'a',
        );
        $b = Foo::create(
            bar: 'b',
        );

        $this->assertEquals(1, $a->id->id);
        $a->save();
        $this->assertEquals(1, $a->id->id);
    }

    public function test_complex_query(): void
    {
        $this->migrate(
            CreateMigrationsTable::class,
            CreateAuthorTable::class,
            CreateBookTable::class,
        );

        $book = Book::new(
            title: 'Book Title',
            author: new Author(
                name: 'Author Name',
                type: AuthorType::B,
            ),
        );

        $book = $book->save();

        $book = Book::find($book->id, relations: ['author']);

        $this->assertEquals(1, $book->id->id);
        $this->assertSame('Book Title', $book->title);
        $this->assertSame(AuthorType::B, $book->author->type);
        $this->assertInstanceOf(Author::class, $book->author);
        $this->assertSame('Author Name', $book->author->name);
        $this->assertEquals(1, $book->author->id->id);
    }

    public function test_all_with_relations(): void
    {
        $this->migrate(
            CreateMigrationsTable::class,
            CreateAuthorTable::class,
            CreateBookTable::class,
        );

        Book::new(
            title: 'Book Title',
            author: new Author(
                name: 'Author Name',
                type: AuthorType::B,
            ),
        )->save();

        $books = Book::all(relations: [
            'author',
        ]);

        $this->assertCount(1, $books);

        $book = $books[0];

        $this->assertEquals(1, $book->id->id);
        $this->assertSame('Book Title', $book->title);
        $this->assertSame(AuthorType::B, $book->author->type);
        $this->assertInstanceOf(Author::class, $book->author);
        $this->assertSame('Author Name', $book->author->name);
        $this->assertEquals(1, $book->author->id->id);
    }

    public function test_missing_relation_exception(): void
    {
        $this->migrate(
            CreateMigrationsTable::class,
            CreateATable::class,
            CreateBTable::class,
            CreateCTable::class,
        );

        (new A(
            b: new B(
                c: new C(name: 'test'),
            ),
        ))->save();

        $a = A::query()->first();

        $this->expectException(MissingRelation::class);

        $b = $a->b;
    }

    public function test_missing_value_exception(): void
    {
        $a = map([])->to(AWithValue::class);

        $this->expectException(MissingValue::class);

        $name = $a->name;
    }

    public function test_nested_relations(): void
    {
        $this->migrate(
            CreateMigrationsTable::class,
            CreateATable::class,
            CreateBTable::class,
            CreateCTable::class,
        );

        (new A(
            b: new B(
                c: new C(name: 'test'),
            ),
        ))->save();

        $a = A::query()->with('b.c')->first();
        $this->assertSame('test', $a->b->c->name);

        $a = A::query()->with('b.c')->all()[0];
        $this->assertSame('test', $a->b->c->name);
    }

    public function test_load_belongs_to(): void
    {
        $this->migrate(
            CreateMigrationsTable::class,
            CreateATable::class,
            CreateBTable::class,
            CreateCTable::class,
        );

        (new A(
            b: new B(
                c: new C(name: 'test'),
            ),
        ))->save();

        $a = A::query()->first();
        $this->assertFalse(isset($a->b));

        $a->load('b.c');
        $this->assertTrue(isset($a->b));
        $this->assertTrue(isset($a->b->c));
    }

    public function test_has_many_relations(): void
    {
        $this->migrate(
            CreateMigrationsTable::class,
            CreateAuthorTable::class,
            CreateBookTable::class,
        );

        $author = (new Author(
            name: 'Author Name',
            type: AuthorType::B,
        ))->save();

        Book::new(
            title: 'Book Title',
            // TODO: nested saves
            author: $author,
        )->save();

        Book::new(
            title: 'Timeline Taxi',
            author: $author,
        )->save();

        $author = Author::query()->with('books')->first();

        $this->assertCount(2, $author->books);
    }

    public function test_has_many_through_relation(): void
    {
        $this->migrate(
            CreateMigrationsTable::class,
            CreateHasManyParentTable::class,
            CreateHasManyChildTable::class,
            CreateHasManyThroughTable::class,
        );

        $parent = (new ParentModel(name: 'parent'))->save();

        $childA = (new ChildModel(name: 'A'))->save();
        $childB = (new ChildModel(name: 'B'))->save();

        (new ThroughModel(parent: $parent, child: $childA))->save();
        (new ThroughModel(parent: $parent, child: $childB))->save();

        $parent = ParentModel::find($parent->id, ['through.child']);

        $this->assertSame('A', $parent->through[1]->child->name);
        $this->assertSame('B', $parent->through[2]->child->name);
    }

    public function test_empty_has_many_relation(): void
    {
        $this->migrate(
            CreateMigrationsTable::class,
            CreateHasManyParentTable::class,
            CreateHasManyChildTable::class,
            CreateHasManyThroughTable::class,
        );

        $parent = (new ParentModel(name: 'parent'))->save();

        $parent = ParentModel::find($parent->id, ['through.child']);

        $this->assertInstanceOf(ParentModel::class, $parent);
        $this->assertEmpty($parent->through);
    }

    public function test_has_one_relation(): void
    {
        $this->migrate(
            CreateMigrationsTable::class,
            CreateHasManyParentTable::class,
            CreateHasManyChildTable::class,
            CreateHasManyThroughTable::class,
        );

        $parent = (new ParentModel(name: 'parent'))->save();

        $childA = (new ChildModel(name: 'A'))->save();

        $childB = (new ChildModel(name: 'B'))->save();

        (new ThroughModel(parent: $parent, child: $childA, child2: $childB))->save();

        $child = ChildModel::find($childA->id, ['through.parent']);
        $child2 = ChildModel::find($childB->id, ['through2.parent']);

        $this->assertSame('parent', $child->through->parent->name);
        $this->assertSame('parent', $child2->through2->parent->name);
    }

    public function test_invalid_has_one_relation(): void
    {
        $this->migrate(
            CreateMigrationsTable::class,
            CreateHasManyParentTable::class,
            CreateHasManyChildTable::class,
            CreateHasManyThroughTable::class,
        );

        $parent = (new ParentModel(name: 'parent'))->save();

        $childA = (new ChildModel(name: 'A'))->save();

        $childB = (new ChildModel(name: 'B'))->save();

        (new ThroughModel(parent: $parent, child: $childA, child2: $childB))->save();

        $child = ChildModel::find($childA->id, ['through.parent']);
        $child2 = ChildModel::find($childB->id, ['through2.parent']);

        $this->assertSame('parent', $child->through->parent->name);
        $this->assertSame('parent', $child2->through2->parent->name);
    }

    public function test_lazy_load(): void
    {
        $this->migrate(
            CreateMigrationsTable::class,
            CreateATable::class,
            CreateBTable::class,
            CreateCTable::class,
        );

        (new AWithLazy(
            b: new B(
                c: new C(name: 'test'),
            ),
        ))->save();

        $a = AWithLazy::query()->first();

        $this->assertFalse(isset($a->b));

        /** @phpstan-ignore expr.resultUnused */
        $a->b; // The side effect from accessing ->b will cause it to load

        $this->assertTrue(isset($a->b));
    }

    public function test_eager_load(): void
    {
        $this->migrate(
            CreateMigrationsTable::class,
            CreateATable::class,
            CreateBTable::class,
            CreateCTable::class,
        );

        (new AWithLazy(
            b: new B(
                c: new C(name: 'test'),
            ),
        ))->save();

        $a = AWithEager::query()->first();
        $this->assertTrue(isset($a->b->c));
    }

    public function test_no_result(): void
    {
        $this->migrate(
            CreateMigrationsTable::class,
            CreateATable::class,
            CreateBTable::class,
            CreateCTable::class,
        );

        $this->assertNull(A::query()->first());
    }

    public function test_update_or_create(): void
    {
        $this->migrate(
            CreateMigrationsTable::class,
            CreateAuthorTable::class,
            CreateBookTable::class,
        );

        Book::new(
            title: 'A',
            author: new Author(
                name: 'Author Name',
                type: AuthorType::B,
            ),
        )->save();

        Book::updateOrCreate(
            ['title' => 'A'],
            ['title' => 'B'],
        );

        $this->assertNull(Book::query()->whereField('title', 'A')->first());
        $this->assertNotNull(Book::query()->whereField('title', 'B')->first());
    }

    public function test_delete(): void
    {
        $this->migrate(
            CreateMigrationsTable::class,
            FooDatabaseMigration::class,
        );

        $foo = Foo::create(
            bar: 'baz',
        );

        $bar = Foo::create(
            bar: 'baz',
        );

        $foo->delete();

        $this->assertNull(Foo::find($foo->getId()));
        $this->assertNotNull(Foo::find($bar->getId()));
    }

    public function test_property_with_carbon_type(): void
    {
        $this->migrate(
            CreateMigrationsTable::class,
            CreateCarbonModelTable::class,
        );

        new CarbonModel(createdAt: new Carbon('2024-01-01'))->save();

        $model = CarbonModel::query()->first();

        $this->assertTrue($model->createdAt->equalTo(new Carbon('2024-01-01')));
    }
}
