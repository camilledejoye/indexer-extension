<?php

namespace Phpactor\Indexer\Tests\Integration\Tolerant\Indexer;

use Generator;
use Phpactor\Indexer\Adapter\Tolerant\Indexer\ClassLikeReferenceIndexer;
use Phpactor\Indexer\Model\LocationConfidence;
use Phpactor\Indexer\Tests\Integration\Tolerant\TolerantIndexerTestCase;

class ClassLikeReferenceIndexerTest extends TolerantIndexerTestCase
{
    /**
     * @dataProvider provideClasses
     * @param array{int,int,int} $expectedCounts
     */
    public function testMembers(string $manifest, string $fqn, array $expectedCounts): void
    {
        $this->workspace()->reset();
        $this->workspace()->loadManifest($manifest);
        $index = $this->createIndex();
        $this->runIndexer(new ClassLikeReferenceIndexer(), $index, 'src');

        $counts = [
            LocationConfidence::CONFIDENCE_NOT => 0,
            LocationConfidence::CONFIDENCE_MAYBE => 0,
            LocationConfidence::CONFIDENCE_SURELY => 0,
        ];

        foreach ($this->indexQuery($index)->class()->referencesTo($fqn) as $locationConfidence) {
            $counts[$locationConfidence->__toString()]++;
        }

        self::assertEquals(array_combine(array_keys($counts), $expectedCounts), $counts);
    }

    /**
     * @return Generator<mixed>
     */
    public function provideClasses(): Generator
    {
        yield 'single ref' => [
            "// File: src/file1.php\n<?php Foobar::static()",
            'Foobar',
            [0, 0, 1]
        ];

        yield 'multiple ref' => [
            "// File: src/file1.php\n<?php Foobar::static(); Foobar::static()",
            'Foobar',
            [0, 0, 2]
        ];

        yield 'constant access' => [
            "// File: src/file1.php\n<?php Foobar::class;",
            'Foobar',
            [0, 0, 1]
        ];
    }
}
