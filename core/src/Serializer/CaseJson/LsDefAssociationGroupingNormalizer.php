<?php

namespace App\Serializer\CaseJson;

use App\Entity\Framework\LsDefAssociationGrouping;
use App\Service\Api1Uris;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;

final class LsDefAssociationGroupingNormalizer implements ContextAwareNormalizerInterface
{
    use LastChangeDateTimeTrait;

    public function __construct(
        private Api1Uris $api1Uris,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $data instanceof LsDefAssociationGrouping;
    }

    /**
     * @inheritDoc
     */
    public function normalize($object, string $format = null, array $context = [])
    {
        if (!$object instanceof LsDefAssociationGrouping) {
            return null;
        }

        $jsonLd = $context['case-json-ld'] ?? null;
        $addContext = (null !== $jsonLd) ? ($context['add-case-context'] ?? null) : null;
        $data = [
            '@context' => (null !== $addContext)
                ? 'https://purl.imsglobal.org/spec/case/v1p0/context/imscasev1p0_context_v1p0.jsonld'
                : null,
            'type' => (null !== $addContext)
                ? 'CFAssociationGrouping'
                : null,
            'identifier' => $object->getIdentifier(),
            'uri' => $this->api1Uris->getUri($object),
            'lastChangeDateTime' => $this->getLastChangeDateTime($object),
            'title' => $object->getTitle(),
            'description' => $object->getDescription(),
        ];

        return array_filter($data, static function ($val) {
            return null !== $val;
        });
    }
}
