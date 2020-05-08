<?php

namespace Phpactor\Indexer\Model\Record;

use Phpactor\Indexer\Model\Record;
use Phpactor\Name\FullyQualifiedName;
use Phpactor\Indexer\Model\Record\FullyQualifiedReferenceTrait;

final class ClassRecord extends Record
{
    use FullyQualifiedReferenceTrait;

    private const RECORD_TYPE = 'class';

    /**
     * @var array<string>
     */
    private $implementations = [];

    /**
     * @var array<string>
     */
    private $implements = [];

    /**
     * @var array<string>
     */
    private $references = [];

    /**
     * Type of "class": class, interface or trait
     *
     * @var string
     */
    private $type;

    public static function fromName(string $name): self
    {
        return new self(FullyQualifiedName::fromString($name));
    }

    public function clearImplemented(): void
    {
        $this->implements = [];
    }

    public function addImplementation(FullyQualifiedName $fqn): void
    {
        $this->implementations[(string)$fqn] = (string)$fqn;
    }

    public function addImplements(FullyQualifiedName $fqn): void
    {
        $this->implements[(string)$fqn] = (string)$fqn;
    }

    public function removeClass(FullyQualifiedName $implementedClass): void
    {
        foreach ($this->implementations as $key => $implementation) {
            if ($implementation !== $implementedClass->__toString()) {
                continue;
            }

            unset($this->implementations[$key]);
        }
    }

    public function removeImplementation(FullyQualifiedName $name): bool
    {
        if (!isset($this->implementations[(string)$name])) {
            return false;
        }
        unset($this->implementations[(string)$name]);
        return true;
    }

    /**
     * @return array<string>
     */
    public function implementations(): array
    {
        return $this->implementations;
    }

    /**
     * @return array<string>
     */
    public function implements(): array
    {
        return $this->implements;
    }

    public function type(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function recordType(): string
    {
        return self::RECORD_TYPE;
    }

    /**
     * @return array<string>
     */
    public function references(): array
    {
        return $this->references;
    }
}
