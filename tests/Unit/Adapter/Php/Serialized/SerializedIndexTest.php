<?php

namespace Phpactor\Indexer\Tests\Unit\Adapter\Php\Serialized;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Phpactor\Indexer\Adapter\Php\Serialized\FileRepository;
use Phpactor\Indexer\Adapter\Php\Serialized\SerializedIndex;
use Phpactor\Indexer\Model\RecordSerializer\PhpSerializer;
use Phpactor\Indexer\Tests\IntegrationTestCase;

class SerializedIndexTest extends IntegrationTestCase
{
    public function testIsFreshWithNonExistingFile(): void
    {
        $repo = new FileRepository($this->workspace()->path(), new PhpSerializer());
        $index = new SerializedIndex($repo);
        $info = new \SplFileInfo($this->workspace()->path('no'));
        Assert::assertFalse($index->isFresh($info), 'File doesn\'t exist, so its not fresh');
    }
}
