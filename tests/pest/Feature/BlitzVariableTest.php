<?php

/**
 * Tests the markup generated by the Blitz variable.
 */

use putyourlightson\blitz\variables\BlitzVariable;

test('Include cached tag does not contain unencoded slashes in params', function() {
    $variable = new BlitzVariable();
    $tagString = (string)$variable->includeCached('test');
    preg_match('/_includes\?(.*)/', $tagString, $match);

    expect($match[1])
        ->not()
        ->toContain('/');
});

test('Include cached tag does not contain path param', function() {
    $variable = new BlitzVariable();
    $tagString = (string)$variable->includeCached('test');
    preg_match('/\?(.*)/', $tagString, $match);

    expect($match[1])
        ->not()
        ->toContain(Craft::$app->getConfig()->getGeneral()->pathParam . '=');
});

test('Fetch URI tag does not contain unencoded slashes in params', function() {
    $variable = new BlitzVariable();
    $tagString = (string)$variable->fetchUri('test', ['action' => 'x/y/z']);
    preg_match('/blitz-params="(.*?)"/', $tagString, $match);

    expect($match[1])
        ->not()
        ->toContain('/');
});
