<?php

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Taxonomy\Label;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use Symfony\Component\Serializer\Exception\UnsupportedException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class LabelsDenormalizer implements DenormalizerInterface
{
    /**
     * @inheritdoc
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        if (!$this->supportsDenormalization($data, $class, $format)) {
            throw new UnsupportedException("LabelsDenormalizer does not support {$class}.");
        }

        if (!is_array($data)) {
            throw new UnsupportedException('Labels data should be an array.');
        }

        $visibleLabelsData = isset($data['labels']) ? $data['labels'] : [];
        $hiddenLabelsData = isset($data['hiddenLabels']) ? $data['hiddenLabels'] : [];

        $visibleLabels = array_map([$this, 'denormalizeLabel'], $visibleLabelsData);
        $hiddenLabels = array_map([$this, 'denormalizeHiddenLabel'], $hiddenLabelsData);

        $labels = array_merge($visibleLabels, $hiddenLabels);
        return new Labels(...$labels);
    }

    /**
     * @inheritdoc
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return $type === Labels::class;
    }

    /**
     * @todo Extract to a separate LabelDenormalizer
     * @param string $label
     * @return Label
     */
    private function denormalizeLabel($label)
    {
        return new Label(new LabelName($label));
    }

    /**
     * @todo Extract to a separate HiddenLabelDenormalizer
     * @param string $label
     * @return Label
     */
    private function denormalizeHiddenLabel($label)
    {
        return new Label(new LabelName($label), false);
    }
}
