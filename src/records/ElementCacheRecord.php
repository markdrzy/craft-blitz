<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\blitz\records;

use craft\db\ActiveRecord;
use craft\records\Element;
use yii\db\ActiveQueryInterface;

/**
 * @property int $id
 * @property int $cacheId
 * @property int $elementId
 * @property CacheRecord $cache
 */
class ElementCacheRecord extends ActiveRecord
{
    // Public Static Methods
    // =========================================================================

     /**
     * @inheritdoc
     *
     * @return string
     */
    public static function tableName(): string
    {
        return '{{%blitz_elementcaches}}';
    }

    /**
     * Returns the associated cache
     *
     * @return ActiveQueryInterface
     */
    public function getCache(): ActiveQueryInterface
    {
        return $this->hasOne(CacheRecord::class, ['id' => 'cacheId']);
    }

    /**
     * Returns the associated element
     *
     * @return ActiveQueryInterface
     */
    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'elementId']);
    }
}
