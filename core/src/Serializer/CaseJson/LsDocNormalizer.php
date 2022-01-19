<?php

namespace App\Serializer\CaseJson;

use App\Entity\Framework\LsDoc;
use App\Service\Api1Uris;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;

final class LsDocNormalizer implements ContextAwareNormalizerInterface
{
    use DateCallbackTrait;
    use AssociationLinkTrait;
    use LinkUriTrait;
    use LastChangeDateTimeTrait;

    public function __construct(
        private Api1Uris $api1Uris,
        private AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $data instanceof LsDoc && null === ($context['generate-package'] ?? null);
    }

    /**
     * @inheritDoc
     */
    public function normalize($object, string $format = null, array $context = [])
    {
        if (!$object instanceof LsDoc) {
            return null;
        }

        $jsonLd = $context['case-json-ld'] ?? null;
        $addContext = (null !== $jsonLd) ? ($context['add-case-context'] ?? null) : null;
        $subject = $object->getSubject();
        $subjectURIs = $object->getSubjects();
        $data = [
            '@context' => (null !== $addContext)
                ? 'https://purl.imsglobal.org/spec/case/v1p0/context/imscasev1p0_context_v1p0.jsonld'
                : null,
            'type' => (null !== $addContext)
                ? 'CFDocument'
                : null,
            'identifier' => $object->getIdentifier(),
            'uri' => $this->api1Uris->getUri($object),
            'creator' => $object->getCreator(),
            'title' => $object->getTitle(),
            'lastChangeDateTime' => $this->getLastChangeDateTime($object),
            'officialSourceURL' => $object->getOfficialUri(),
            'CFPackageURI' => $this->createPackageLinkUri($object, 'LsDoc', $context),
            'publisher' => $object->getPublisher(),
            'description' => $object->getDescription(),
            'subject' => count($subject ?? []) > 0
                ? $subject
                : null,
            'subjectURI' => count($subjectURIs ?? []) > 0
                ? $this->api1Uris->getLinkUriList($subjectURIs)
                : null,
            'language' => $object->getLanguage(),
            'version' => $object->getVersion(),
            'adoptionStatus' => $object->getAdoptionStatus(),
            'statusStartDate' => $this->toDate($object->getStatusStart()),
            'statusEndDate' => $this->toDate($object->getStatusEnd()),
            'licenseURI' => $this->api1Uris->getLinkUri($object->getLicence()),
            'notes' => $object->getNote(),
            'updatedAt' => in_array('updatedAt', $context['groups'] ?? [], true) ? $object->getUpdatedAt() : null,
            'associationSet' => $this->createAssociationLinks($object, $context),
        ];

        return array_filter($data, static function ($val) {
            return null !== $val;
        });
    }
}
