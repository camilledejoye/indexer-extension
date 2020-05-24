<?php

namespace Phpactor\Indexer\Tests\Integration\Tolerant\Indexer;

use Generator;
use Phpactor\Indexer\Adapter\Tolerant\Indexer\ClassDeclarationIndexer;
use Phpactor\Indexer\Model\Record\ClassRecord;
use Phpactor\Indexer\Tests\Integration\Tolerant\TolerantIndexerTestCase;
use RuntimeException;

class ClassDeclarationIndexerTest extends TolerantIndexerTestCase
{
    /**
     * @dataProvider provideImplementations
     */
    public function testImplementations(string $manifest, string $fqn, int $expectedCount): void
    {
        $this->workspace()->reset();
        $this->workspace()->loadManifest($manifest);
        $index = $this->createIndex();
        $this->runIndexer(new ClassDeclarationIndexer(), $index, 'src');

        self::assertCount($expectedCount, $this->indexQuery($index)->class()->implementing($fqn));
    }

    /**
     * @return Generator<mixed>
     */
    public function provideImplementations(): Generator
    {
        yield 'no implementations' => [
            "// File: src/file1.php\n<?php class Barfoo {}",
            'Foobar',
            0
        ];

        yield 'class implements' => [
            "// File: src/file1.php\n<?php class Barfoo implements Foobar{}",
            'Foobar',
            1
        ];

        yield 'class implements multiple' => [
            "// File: src/file1.php\n<?php class Barfoo implements Baz, Foobar{}",
            'Foobar',
            1
        ];

        yield 'abstract class implements' => [
            "// File: src/file1.php\n<?php abstract class Barfoo implements Foobar{}",
            'Foobar',
            1
        ];
    }

    /**
     * @dataProvider provideSearch
     * @param array<ClassRecord> $expectedRecords
     */
    public function testSearch(string $manifest, string $search, array $expectedRecords): void
    {
        $this->workspace()->reset();
        $this->workspace()->loadManifest($manifest);
        $index = $this->createIndex();
        $this->runIndexer(new ClassDeclarationIndexer(), $index, 'src');
        $foundRecords = $this->indexQuery($index)->search($search);

        foreach ($expectedRecords as $record) {
            foreach ($foundRecords as $foundRecord) {
                assert($foundRecord instanceof ClassRecord);
                if ($foundRecord->identifier() === $record->identifier()) {
                    self::assertEquals($record->filePath(), $foundRecord->filePath());
                    continue 2;
                }
            }

            throw new RuntimeException(sprintf(
                'Record "%s" not found',
                $record->identifier()
            ));
        }

        $this->addToAssertionCount(1);
    }

    /**
     * @return Generator<mixed>
     */
    public function provideSearch(): Generator
    {
        yield 'no results' => [
            "// File: src/file1.php\n<?php class Barfoo {}",
            'Foobar',
            []
        ];

        yield 'exact match' => [
            "// File: src/file1.php\n<?php class Barfoo implements Foobar{}",
            'Barfoo',
            [ClassRecord::fromName('Barfoo')->setFilePath($this->workspace()->path('src/file1.php'))]
        ];

        yield 'namespaced match' => [
            "// File: src/file1.php\n<?php namespace Bar; class Barfoo implements Foobar{}",
            'Barfoo',
            [ClassRecord::fromName('Bar\Barfoo')->setFilePath($this->workspace()->path('src/file1.php'))]
        ];
    }
}
