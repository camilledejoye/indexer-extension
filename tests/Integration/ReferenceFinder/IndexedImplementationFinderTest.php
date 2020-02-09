<?php

namespace Phpactor\WorkspaceQuery\Tests\Integration\ReferenceFinder;

use Phpactor\WorkspaceQuery\Adapter\ReferenceFinder\IndexedImplementationFinder;
use Phpactor\WorkspaceQuery\Tests\Integration\InMemoryTestCase;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocumentBuilder;
use RuntimeException;

class IndexedImplementationFinderTest extends InMemoryTestCase
{
    protected function setUp(): void
    {
        $this->workspace()->reset();
        $this->workspace()->loadManifest(file_get_contents(__DIR__ . '/../Manifest/buildIndex.php.test'));
    }

    public function testFinder()
    {
        $index = $this->createIndex();
        $builder = $this->createBuilder($index);
        $builder->build();

        $implementationFinder = new IndexedImplementationFinder($index, $builder, $this->createReflector());
        $locations = $implementationFinder->findImplementations(TextDocumentBuilder::create(
            <<<'EOT'
<?php

new Index();
EOT
        )->build(), ByteOffset::fromInt(8));

        self::assertCount(2, $locations);
    }

    public function testThrowsExceptionIfIndexNotInitialized()
    {
        $this->expectException(RuntimeException::class);
        $index = $this->createIndex();
        $index->reset();
        $implementationFinder = new IndexedImplementationFinder(
            $index,
            $this->createBuilder($index),
            $this->createReflector()
        );
        $locations = $implementationFinder->findImplementations(TextDocumentBuilder::create(
            <<<'EOT'
<?php

new Index();
EOT
        )->build(), ByteOffset::fromInt(8));
    }
}