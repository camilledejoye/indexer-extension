<?php

namespace Phpactor\Indexer\Tests\Integration\Tolerant\Indexer;

use Generator;
use Phpactor\Indexer\Adapter\Tolerant\Indexer\MemberIndexer;
use Phpactor\Indexer\Model\LocationConfidence;
use Phpactor\Indexer\Model\MemberReference;
use Phpactor\Indexer\Model\Record\MemberRecord;
use Phpactor\Indexer\Tests\Integration\Tolerant\TolerantIndexerTestCase;

class MemberIndexerTest extends TolerantIndexerTestCase
{
    /**
     * @dataProvider provideStaticAccess
     * @dataProvider provideInstanceAccess
     * @param array{int,int,int} $expectedCounts
     */
    public function testMembers(string $manifest, MemberReference $memberReference, array $expectedCounts): void
    {
        $this->workspace()->reset();
        $this->workspace()->loadManifest($manifest);
        $index = $this->createIndex();
        $this->runIndexer(new MemberIndexer(), $index, 'src');

        $memberRecord = $index->get(MemberRecord::fromMemberReference($memberReference));
        assert($memberRecord instanceof MemberRecord);

        $counts = [
            LocationConfidence::CONFIDENCE_NOT => 0,
            LocationConfidence::CONFIDENCE_MAYBE => 0,
            LocationConfidence::CONFIDENCE_SURELY => 0,
        ];

        foreach ($this->indexQuery($index)->member()->referencesTo(
            $memberReference->type(),
            $memberReference->memberName(),
            $memberReference->containerType()
        ) as $locationCondidence) {
            $counts[$locationCondidence->__toString()]++;
        }

        self::assertEquals(array_combine(array_keys($counts), $expectedCounts), $counts);
    }

    /**
     * @return Generator<mixed>
     */
    public function provideStaticAccess(): Generator
    {
        yield 'single ref' => [
            "// File: src/file1.php\n<?php Foobar::static()",
            MemberReference::create(MemberRecord::TYPE_METHOD, 'Foobar', 'static'),
            [0, 0, 1]
        ];

        yield '> 1 same name method with different container type and specified search type' => [
            "// File: src/file1.php\n<?php Foobar::static(); Barfoo::static();",
            MemberReference::create(MemberRecord::TYPE_METHOD, 'Foobar', 'static'),
            [ 1, 0, 1 ],
        ];

        yield '> 1 same name method with different container type and no specified search type' => [
            "// File: src/file1.php\n<?php Foobar::static(); Barfoo::static();",
            MemberReference::create(MemberRecord::TYPE_METHOD, null, 'static'),
            [ 0, 0, 2 ],
        ];

        yield 'multiple ref' => [
            "// File: src/file1.php\n<?php Foobar::static(); Foobar::static();",
            MemberReference::create(MemberRecord::TYPE_METHOD, 'Foobar', 'static'),
            [ 0, 0, 2 ],
        ];

        yield MemberRecord::TYPE_CONSTANT => [
            "// File: src/file1.php\n<?php Foobar::FOOBAR;",
            MemberReference::create(MemberRecord::TYPE_CONSTANT, 'Foobar', 'FOOBAR'),
            [ 0, 0, 1 ],
        ];

        yield 'constant in call' => [
            "// File: src/file1.php\n<?php get(Foobar::FOOBAR);",
            MemberReference::create(MemberRecord::TYPE_CONSTANT, 'Foobar', 'FOOBAR'),
            [ 0, 0, 1 ]
        ];

        yield MemberRecord::TYPE_PROPERTY => [
            "// File: src/file1.php\n<?php Foobar::\$foobar;",
            MemberReference::create(MemberRecord::TYPE_PROPERTY, 'Foobar', '$foobar'),
            [ 0, 0, 1 ]
        ];

        yield 'namespaced static access' => [
            "// File: src/file1.php\n<?php use Barfoo\\Foobar; Foobar::hello();",
            MemberReference::create(MemberRecord::TYPE_METHOD, 'Barfoo\\Foobar', 'hello'),
            [ 0, 0, 1 ]
        ];
    }

    /**
     * @return Generator<mixed>
     */
    public function provideInstanceAccess(): Generator
    {
        yield 'method call with wrong container type' => [
            "// File: src/file1.php\n<?php class Foobar {}; \$foobar = new Foobar(); \$foobar->hello();",
            MemberReference::create(MemberRecord::TYPE_METHOD, 'Barfoo', 'hello'),
            [ 1, 0, 0 ],
        ];

        yield 'method call' => [
            "// File: src/file1.php\n<?php \$foobar->hello();",
            MemberReference::create(MemberRecord::TYPE_METHOD, 'Foobar', 'hello'),
            [ 0, 1, 0 ],
        ];

        yield 'property access' => [
            "// File: src/file1.php\n<?php \$foobar->hello;",
            MemberReference::create(MemberRecord::TYPE_PROPERTY, 'Foobar', 'hello'),
            [ 0, 1, 0 ],
        ];

        yield 'resolvable property instance container type' => [
            "// File: src/file1.php\n<?php class Foobar {}; \$foobar = new Foobar(); \$foobar->hello;",
            MemberReference::create(MemberRecord::TYPE_PROPERTY, 'Foobar', 'hello'),
            [ 0, 0, 1 ],
        ];
    }
}
