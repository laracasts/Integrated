<?php

namespace Laracasts\Integrated;

use Laracasts\Integrated\Str;
use ReflectionClass;

class AnnotationReader
{
    /**
     * Create a new AnnotationReader instance.
     *
     * @param mixed $reference
     */
    public function __construct($reference)
    {
        $this->reference = $reference;
    }

    /**
     * Get method names for the referenced object
     * which contain the given annotation.
     *
     * @param  string $annotation
     * @return array
     */
    public function having($annotation)
    {
        $methods = [];

        foreach ($this->getTraits() as $trait) {
            foreach ($trait->getMethods() as $method) {
                if (Str::contains($method->getDocComment(), "@{$annotation}")) {
                    $methods[] = $method->getName();
                }
            }
        }

        return $methods;
    }

    /**
     * Load any user-created traits for the current object.
     *
     * @return ReflectionClass
     */
    protected function getTraits()
    {
        return (new ReflectionClass($this->reference))->getTraits();
    }
}
