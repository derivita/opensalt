<?php

namespace App\Serializer\CaseJson;

use App\Entity\Framework\CfRubricCriterion;
use App\Service\Api1Uris;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

final class CfRubricCriterionNormalizer implements NormalizerAwareInterface, ContextAwareNormalizerInterface
{
    use NormalizerAwareTrait;
    use LinkUriTrait;
    use LastChangeDateTimeTrait;

    public function __construct(
        private Api1Uris $api1Uris,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function supportsNormalization($data, string $format = null, array $context = [])
    {
        return $data instanceof CfRubricCriterion;
    }

    /**
     * @inheritDoc
     */
    public function normalize($object, string $format = null, array $context = [])
    {
        if (!$object instanceof CfRubricCriterion) {
            return null;
        }

        $jsonLd = $context['case-json-ld'] ?? null;
        $addContext = (null !== $jsonLd) ? ($context['add-case-context'] ?? null) : null;
        if (null !== ($context['add-case-context'] ?? null)) {
            unset($context['add-case-context']);
        }
        $data = [
            '@context' => (null !== $addContext)
                ? 'https://purl.imsglobal.org/spec/case/v1p0/context/imscasev1p0_context_v1p0.jsonld'
                : null,
            'id' => (null !== $jsonLd)
                ? $this->api1Uris->getUri($object)
                : null,
            'type' => (null !== $jsonLd)
                ? 'CFRubricCriterion'
                : null,
            'identifier' => $object->getIdentifier(),
            'uri' => $this->api1Uris->getUri($object),
            'lastChangeDateTime' => $this->getLastChangeDateTime($object),
            'CFItemURI' => $this->createLinkUri($object->getItem(), $context),
            'rubricId' => in_array('CfRubricCriterion', $context['groups'] ?? [], true)
                ? $object->getRubric()->getIdentifier()
                : null,
            'category' => $object->getCategory(),
            'description' => $object->getDescription(),
            'position' => $object->getPosition(),
            'weight' => $object->getWeight(),
        ];

        foreach ($object->getLevels() as $level) {
            $data['CFRubricCriterionLevels'][] = $this->normalizer->normalize($level, $format, $context);
        }

        return array_filter($data, static function ($val) {
            return null !== $val;
        });
    }
}
