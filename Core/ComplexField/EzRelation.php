<?php

namespace Kaliop\eZMigrationBundle\Core\ComplexField;

use eZ\Publish\Core\FieldType\Relation\Value;
use Kaliop\eZMigrationBundle\API\FieldValueImporterInterface;
use Kaliop\eZMigrationBundle\API\FieldDefinitionConverterInterface;
use Kaliop\eZMigrationBundle\Core\Matcher\ContentMatcher;

class EzRelation extends AbstractComplexField implements FieldValueImporterInterface, FieldDefinitionConverterInterface
{
    protected $contentMatcher;

    public function __construct(ContentMatcher $contentMatcher)
    {
        $this->contentMatcher = $contentMatcher;
    }

    /**
     * Creates a value object to use as the field value when setting an ez relation field type.
     *
     * @param array|string|int $fieldValue The definition of the field value, structured in the yml file
     * @param array $context The context for execution of the current migrations. Contains f.e. the path to the migration
     * @return Value
     */
    public function hashToFieldValue($fieldValue, array $context = array())
    {
        if (count($fieldValue) == 1 && isset($fieldValue['destinationContentId'])) {
            // fromHash format
            $id = $fieldValue['destinationContentId'];
        } else {
            // simplified format
            $id = $fieldValue;
        }

        if ($id === null) {
            return new Value();
        }

        // 1. resolve relations
        $id = $this->referenceResolver->resolveReference($id);
        // 2. resolve remote ids
        $id = $this->contentMatcher->matchOneByKey($id)->id;

        return new Value($id);
    }

    public function fieldSettingsToHash($settingsValue, array $context = array())
    {
        // work around https://jira.ez.no/browse/EZP-26916
        if (is_array($settingsValue) && isset($settingsValue['selectionRoot']) && $settingsValue['selectionRoot'] === '') {
            $settingsValue['selectionRoot'] = null;
        }
        return $settingsValue;
    }

    public function hashToFieldSettings($settingsHash, array $context = array())
    {
        return $settingsHash;
    }
}
