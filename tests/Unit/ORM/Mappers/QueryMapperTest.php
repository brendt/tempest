<?php

declare(strict_types=1);

namespace Tests\Tempest\Unit\ORM\Mappers;

use App\Modules\Books\Models\Author;
use App\Modules\Books\Models\Book;
use Tempest\Database\Id;
use Tempest\Database\Query;
use function Tempest\make;
use Tests\Tempest\TestCase;

class QueryMapperTest extends TestCase
{
    /** @test */
    public function create_query()
    {
        $author = Author::new(name: 'test');

        $query = make(Query::class)->from($author);

        $table = Author::table();

        $this->assertSame("INSERT INTO {$table} (name) VALUES (:name);", $query->getSql());
        $this->assertSame(['name' => 'test'], $query->bindings);
    }

    /** @test */
    public function create_query_with_nested_relation()
    {
        $book = Book::new(
            title: 'Book Title',
            author: Author::new(
                name: 'Author Name',
            ),
        );

        $query = make(Query::class)->from($book);

        $bookTable = Book::table();

        $this->assertSame("INSERT INTO {$bookTable} (title, author_id) VALUES (:title, :author_id);", $query->getSql());
        $this->assertSame(['title', 'author_id'], array_keys($query->bindings));
        $this->assertSame('Book Title', $query->bindings['title']);

        $authorTable = Author::table();

        $authorQuery = $query->bindings['author_id'];
        $this->assertInstanceOf(Query::class, $authorQuery);
        $this->assertSame("INSERT INTO {$authorTable} (name) VALUES (:name);", $authorQuery->getSql());
        $this->assertSame('Author Name', $authorQuery->bindings['name']);
    }

    /** @test */
    public function update_query()
    {
        $author = Author::new(id: new Id(1), name: 'other');

        $query = make(Query::class)->from($author);

        $table = Author::table();

        $this->assertSame("UPDATE {$table} SET name = :name WHERE id = 1;", $query->getSql());
        $this->assertSame(['name' => 'other'], $query->bindings);
    }
}
