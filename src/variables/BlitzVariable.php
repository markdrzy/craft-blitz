<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\blitz\variables;

use Craft;
use craft\helpers\Template;
use craft\helpers\UrlHelper;
use craft\web\View;
use putyourlightson\blitz\Blitz;
use putyourlightson\blitz\models\CacheOptionsModel;
use Twig\Markup;
use yii\web\NotFoundHttpException;

class BlitzVariable
{
    /**
     * @var int
     */
    private $_injected = 0;

    // Public Methods
    // =========================================================================

    /**
     * Returns script to get the output of a URI.
     *
     * @param string $uri
     * @param array $params
     * @return Markup
     */
    public function getUri(string $uri, array $params = []): Markup
    {
        $params['no-cache'] = 1;

        return $this->_getScript($uri, $params);
    }

    /**
     * Returns script to get the output of a template.
     *
     * @param string $template
     * @param array $params
     * @return Markup
     */
    public function getTemplate(string $template, array $params = []): Markup
    {
        // Ensure template exists
        if (!Craft::$app->getView()->resolveTemplate($template)) {
            throw new NotFoundHttpException('Template not found: '.$template);
        }

        $uri = 'blitz/templates/get';

        // Hash the template
        $template = Craft::$app->getSecurity()->hashData($template);

        // Add template and passed in params to the params
        $params = [
            'template' => $template,
            'params' => $params,
            'siteId' => Craft::$app->getSites()->getCurrentSite()->id,
        ];

        return $this->_getScript($uri, $params);
    }

    /**
     * Returns a script to get a CSRF input field.
     *
     * @return Markup
     */
    public function csrfInput(): Markup
    {
        $uri = 'blitz/csrf/input';

        return $this->_getScript($uri);
    }

    /**
     * Returns a script to get the CSRF param.
     *
     * @return Markup
     */
    public function csrfParam(): Markup
    {
        $uri = 'blitz/csrf/param';

        return $this->_getScript($uri);
    }

    /**
     * Returns a script to get a CSRF token.
     *
     * @return Markup
     */
    public function csrfToken(): Markup
    {
        $uri = 'blitz/csrf/token';

        return $this->_getScript($uri);
    }

    /**
     * Returns options for the current page cache, first setting any parameters provided.
     *
     * @param array $params
     *
     * @return CacheOptionsModel
     */
    public function options(array $params = []): CacheOptionsModel
    {
        $options = Blitz::$plugin->generateCache->options;

        if (isset($params['cacheDuration'])) {
            $options->cacheDuration($params['cacheDuration']);
        }

        $options->setAttributes($params, false);

        if ($options->validate()) {
            Blitz::$plugin->generateCache->options = $options;
        }

        return Blitz::$plugin->generateCache->options;
    }

    /**
     * Returns whether the `@web` alias is used in any site's base URL.
     *
     * @return bool
     */
    public static function getWebAliasExists(): bool
    {
        $sites = Craft::$app->getSites()->getAllSites();

        foreach ($sites as $site) {
            if (strpos($site->baseUrl, '@web') !== false) {
                return true;
            }
        }

        return false;
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns a script to inject the output of a URI.
     *
     * @param string $uri
     * @param array $params
     * @return Markup
     */
    private function _getScript(string $uri, array $params = []): Markup
    {
        $view = Craft::$app->getView();
        $js = '';

        if ($this->_injected === 0) {
            $blitzInjectScript = Craft::getAlias('@putyourlightson/blitz/resources/js/blitzInjectScript.js');

            if (file_exists($blitzInjectScript)) {
                $js = file_get_contents($blitzInjectScript);
                $js = str_replace('{injectScriptEvent}', Blitz::$plugin->settings->injectScriptEvent, $js);
            }
        }

        // Create polyfills using https://polyfill.io/v3/url-builder/.
        $polyfills = ['fetch', 'Promise', 'CustomEvent'];
        $polyfillUrl = 'https://polyfill.io/v3/polyfill.min.js?features='.implode('%2C', $polyfills);

        // Register polyfills for IE11 only, using the `module/nomodule` pattern.
        // https://3perf.com/blog/polyfills/#modulenomodule
        $view->registerJsFile($polyfillUrl, ['nomodule' => true]);
        $view->registerJs($js, View::POS_END);

        $this->_injected++;
        $id = $this->_injected;

        $data = [
            'id' => $id,
            'uri' => UrlHelper::actionUrl($uri, null, null, false),
            'params' => http_build_query($params),
        ];

        foreach ($data as $key => &$value) {
            $value = 'data-blitz-'.$key.'="'.$value.'"';
        }

        $output = '<span class="blitz-inject" id="blitz-inject-'.$id.'" '.implode(' ', $data).'></span>';

        return Template::raw($output);
    }
}
