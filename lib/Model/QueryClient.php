<?php

namespace Phpactor\Indexer\Model;

use Phpactor\Indexer\Model\Query\ClassQuery;
use Phpactor\Indexer\Model\Query\FileQuery;
use Phpactor\Indexer\Model\Query\FunctionQuery;
use Phpactor\Indexer\Model\Query\MemberQuery;
use Phpactor\Indexer\Model\RecordReferenceEnhancer\NullRecordReferenceEnhancer;

class QueryClient
{
    /**
     * @var ClassQuery
     */
    private $classQuery;

    /**
     * @var FunctionQuery
     */
    private $functionQuery;

    /**
     * @var FileQuery
     */
    private $fileQuery;

    /**
     * @var MemberQuery
     */
    private $memberQuery;

    /**
     * @var Index
     */
    private $index;

    public function __construct(Index $index, ?RecordReferenceEnhancer $enhancer = null)
    {
        $enhancer = $enhancer ?: new NullRecordReferenceEnhancer();

        $this->classQuery = new ClassQuery($index);
        $this->functionQuery = new FunctionQuery($index);
        $this->fileQuery = new FileQuery($index);
        $this->memberQuery = new MemberQuery($index, $enhancer);
        $this->index = $index;
    }

    public function class(): ClassQuery
    {
        return $this->classQuery;
    }

    public function function(): FunctionQuery
    {
        return $this->functionQuery;
    }

    public function file(): FileQuery
    {
        return $this->fileQuery;
    }

    public function member(): MemberQuery
    {
        return $this->memberQuery;
    }
}
