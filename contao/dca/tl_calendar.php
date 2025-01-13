<?php

declare(strict_types=1);

/*
 * This file is part of SAC Event Feedback.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/sac-event-feedback
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;

PaletteManipulator::create()
    ->addLegend(
        'sac_event_feedback_legend',
        'protected_legend',
        PaletteManipulator::POSITION_BEFORE
    )
    ->addField(
        'enableOnlineEventFeedback',
        'sac_event_feedback_legend',
        PaletteManipulator::POSITION_APPEND
    )
    ->applyToPalette(
        'default',
        'tl_calendar'
    );

// Palettes
$GLOBALS['TL_DCA']['tl_calendar']['palettes']['__selector__'][] = 'enableOnlineEventFeedback';
$GLOBALS['TL_DCA']['tl_calendar']['subpalettes']['enableOnlineEventFeedback'] = 'onlineFeedbackConfiguration,onlineFeedbackNotification,onlineFeedbackPage,onlineFeedbackForm';

$GLOBALS['TL_DCA']['tl_calendar']['fields']['enableOnlineEventFeedback'] = [
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'checkbox',
    'eval'      => ['submitOnChange' => true, 'tl_class' => 'w50'],
    'sql'       => "char(1) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_calendar']['fields']['onlineFeedbackConfiguration'] = [
    'exclude'   => true,
    'search'    => true,
    'inputType' => 'select',
    'eval'      => ['mandatory' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'],
    'sql'       => "varchar(64) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_calendar']['fields']['onlineFeedbackNotification'] = [
    'exclude'   => true,
    'search'    => true,
    'inputType' => 'select',
    'eval'      => ['mandatory' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'],
    'sql'       => "varchar(64) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_calendar']['fields']['onlineFeedbackPage'] = [
    'exclude'    => true,
    'inputType'  => 'pageTree',
    'foreignKey' => 'tl_page.title',
    'eval'       => ['mandatory' => true, 'fieldType' => 'radio', 'tl_class' => 'clr'],
    'sql'        => 'int(10) unsigned NOT NULL default 0',
    'relation'   => ['type' => 'hasOne', 'load' => 'lazy'],
];

$GLOBALS['TL_DCA']['tl_calendar']['fields']['onlineFeedbackForm'] = [
    'exclude'   => true,
    'search'    => true,
    'inputType' => 'select',
    'eval'      => ['mandatory' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'],
    'sql'       => "varchar(64) NOT NULL default ''",
];
