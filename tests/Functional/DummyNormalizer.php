<?php

namespace Cache\CacheBundle\Tests\Functional;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class DummyNormalizer implements NormalizerInterface
{
    public function normalize($object, $format = null, array $context = array())
    {
        return [];
    }

    public function supportsNormalization($data, $format = null)
    {
        return false;
    }
}
