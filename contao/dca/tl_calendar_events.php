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
use Markocupic\SacEventToolBundle\Config\EventType;

PaletteManipulator::create()
    ->addLegend(
        'sac_event_feedback_legend',
        'gallery_legend',
        PaletteManipulator::POSITION_BEFORE
    )
    ->addField(
        'enableOnlineEventFeedback',
        'sac_event_feedback_legend',
        PaletteManipulator::POSITION_APPEND
    )
    ->applyToPalette(
        'default',
        'tl_calendar_events'
    )
    ->applyToPalette(
        EventType::TOUR,
        'tl_calendar_events'
    )
    ->applyToPalette(
        EventType::LAST_MINUTE_TOUR,
        'tl_calendar_events'
    )
    ->applyToPalette(
        EventType::COURSE,
        'tl_calendar_events'
    )
    ->applyToPalette(
        EventType::GENERAL_EVENT,
        'tl_calendar_events'
    );

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['enableOnlineEventFeedback'] = [
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50'],
    'sql'       => "char(1) NOT NULL default ''",
];
