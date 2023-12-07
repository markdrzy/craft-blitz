<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\blitz\utilities;

use Craft;
use craft\base\Element;
use craft\base\Utility;
use craft\db\Table;
use putyourlightson\blitz\assets\BlitzAsset;
use putyourlightson\blitz\records\CacheRecord;
use putyourlightson\blitz\records\ElementCacheRecord;
use putyourlightson\blitz\records\ElementQueryCacheRecord;
use putyourlightson\sprig\Sprig;
use yii\db\Query;

/**
 * @since 4.10.0
 */
class DiagnosticsUtility extends Utility
{
    public function init(): void
    {
        parent::init();

        Sprig::getInstance()->init();
    }

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('blitz', 'Blitz Diagnostics');
    }

    /**
     * @inheritdoc
     */
    public static function id(): string
    {
        return 'blitz-diagnostics';
    }

    /**
     * @inheritdoc
     */
    public static function iconPath(): ?string
    {
        $iconPath = Craft::getAlias('@putyourlightson/blitz/resources/icons/diagnostics.svg');

        if (!is_string($iconPath)) {
            return null;
        }

        return $iconPath;
    }

    /**
     * @inheritdoc
     */
    public static function contentHtml(): string
    {
        Craft::$app->getView()->registerAssetBundle(BlitzAsset::class);

        $id = Craft::$app->getRequest()->getParam('id');

        if ($id) {
            $elementType = Craft::$app->getRequest()->getParam('elementType');
            if ($elementType) {
                return Craft::$app->getView()->renderTemplate('blitz/_utilities/diagnostics/elements', [
                    'page' => self::getPage($id),
                    'elementType' => $elementType,
                    'elements' => self::getElements($id, $elementType),
                ]);
            }

            $elementQueryType = Craft::$app->getRequest()->getParam('elementQueryType');
            if ($elementQueryType) {
                return Craft::$app->getView()->renderTemplate('blitz/_utilities/diagnostics/elementQueries', [
                    'page' => self::getPage($id),
                    'elementQueryType' => $elementQueryType,
                    'elementQueries' => self::getElementQueries($id, $elementQueryType),
                ]);
            }

            return Craft::$app->getView()->renderTemplate('blitz/_utilities/diagnostics/page', [
                'page' => self::getPage($id),
                'elementTypes' => self::getElementQueryTypes($id),
                'elementQueryTypes' => self::getElementQueryTypes($id),
            ]);
        }

        return Craft::$app->getView()->renderTemplate('blitz/_utilities/diagnostics/index');
    }

    public static function getPagesQuery(): Query
    {
        return CacheRecord::find()
            ->select(['id', 'uri', 'elementCount', 'elementQueryCount'])
            ->leftJoin([
                'elements' => ElementCacheRecord::find()
                    ->select(['cacheId', 'count(*) as elementCount'])
                    ->groupBy(['cacheId']),
            ], 'id = elements.cacheId')
            ->leftJoin([
                'elementQueries' => ElementQueryCacheRecord::find()
                    ->select(['cacheId', 'count(*) as elementQueryCount'])
                    ->groupBy(['cacheId']),
            ], 'id = elementQueries.cacheId');
    }

    public static function getPage(int $id): array|null
    {
        return CacheRecord::find()
            ->select(['id', 'uri'])
            ->where(['id' => $id])
            ->asArray()
            ->one();
    }

    public static function getElementTypes(int $id): array
    {
        return ElementCacheRecord::find()
            ->select(['cacheId', 'count(*) as count', 'type'])
            ->innerJoin(Table::ELEMENTS, 'id = elementId')
            ->where(['cacheId' => $id])
            ->groupBy(['type'])
            ->orderBy(['count' => SORT_DESC])
            ->asArray()
            ->all();
    }

    public static function getElementQueryTypes(int $id): array
    {
        return ElementQueryCacheRecord::find()
            ->select(['cacheId', 'count(*) as count', 'type'])
            ->innerJoinWith('elementQuery')
            ->where(['cacheId' => $id])
            ->groupBy(['type'])
            ->orderBy(['count' => SORT_DESC])
            ->asArray()
            ->all();
    }

    public static function getElements(int $id, string $elementType): array
    {
        $elementIds = ElementCacheRecord::find()
            ->select(['id'])
            ->innerJoin(Table::ELEMENTS, 'id = elementId')
            ->where([
                'cacheId' => $id,
                'type' => $elementType,
            ])
            ->asArray()
            ->column();

        /** @var Element $elementType */
        return $elementType::find()
            ->id($elementIds)
            ->all();
    }

    public static function getElementQueries(int $id, string $elementQueryType): array
    {
        $elementQueries = ElementQueryCacheRecord::find()
            ->select(['params'])
            ->innerJoinWith('elementQuery')
            ->where([
                'cacheId' => $id,
                'type' => $elementQueryType,
            ])
            ->asArray()
            ->all();

        foreach ($elementQueries as &$elementQuery) {
            /** @var Element $elementQueryType */
            $elementQuery['sql'] = $elementQueryType::find()
                ->status(null)
                ->createCommand()
                ->getRawSql();
        }

        return $elementQueries;
    }
}