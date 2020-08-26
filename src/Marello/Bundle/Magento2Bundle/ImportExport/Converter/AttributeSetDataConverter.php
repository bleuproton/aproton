<?php

namespace Marello\Bundle\Magento2Bundle\ImportExport\Converter;

use Oro\Bundle\IntegrationBundle\ImportExport\DataConverter\IntegrationAwareDataConverter;

class AttributeSetDataConverter extends IntegrationAwareDataConverter
{
    public const ID_COLUMN_NAME = 'attribute_set_id';
    public const NAME_COLUMN_NAME = 'attribute_set_name';
    public const ATTRIBUTE_FAMILY_COLUMN_ID = 'attributeFamilyId';

    private const NAME_MAX_LENGTH = 255;
    private const CODE_MAX_LENGTH = 32;
    private const ELLIPSIS = '...';
    
    /**
     * {@inheritdoc}
     */
    protected function getHeaderConversionRules()
    {
        return [
            self::ID_COLUMN_NAME => 'originId',
            self::NAME_COLUMN_NAME => 'attributeSetName'
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getBackendHeader()
    {
        return array_values($this->getHeaderConversionRules());
    }

    /**
     * {@inheritdoc}
     */
    public function convertToImportFormat(array $importedRecord, $skipNullValues = true)
    {
        if (!empty($importedRecord['attribute_set_name']) &&
            mb_strlen($importedRecord['attribute_set_name']) > self::NAME_MAX_LENGTH
        ) {
            $importedRecord['attribute_set_name'] = $this->cutFieldToLength(
                $importedRecord['attribute_set_name'],
                self::NAME_MAX_LENGTH
            );
        }
        $importedRecord['attributeFamily:code'] = $importedRecord['attribute_set_name'];
        return parent::convertToImportFormat($importedRecord, $skipNullValues);
    }

    /**
     * Cuts field value to max length and add ellipsis
     *
     * @param string $fieldValue
     * @param int $maxLength
     *
     * @return string
     */
    private function cutFieldToLength($fieldValue, $maxLength): string
    {
        $ellipsisLength = \mb_strlen(self::ELLIPSIS);
        $fieldValue = sprintf(
            '%s%s',
            \mb_strcut($fieldValue, 0, $maxLength - $ellipsisLength),
            self::ELLIPSIS
        );

        return $fieldValue;
    }
}
