<?php

/*
 * This file is part of SAC Event Evaluation Bundle.
 *
 * (c) Marko Cupic 2021 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/sac-event-evaluation
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;

PaletteManipulator::create()
	->addLegend('sac_event_evaluation_legend', 'gallery_legend', PaletteManipulator::POSITION_BEFORE)
	->addField('enableOnlineEventEvaluation', 'sac_event_evaluation_legend', PaletteManipulator::POSITION_APPEND)
	->applyToPalette('default', 'tl_calendar_events')
	->applyToPalette('tour', 'tl_calendar_events')
	->applyToPalette('lastMinuteTour', 'tl_calendar_events')
	->applyToPalette('course', 'tl_calendar_events')
	->applyToPalette('generalEvent', 'tl_calendar_events')
;

$GLOBALS['TL_DCA']['tl_calendar_events_member']['fields']['doOnlineEventEvaluation'] = array
(
	'exclude'                 => true,
	'search'                  => true,
	'inputType'               => 'checkbox',
	'eval'                    => array('tl_class'=>'w50'),
	'sql'                     => "char(1) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_calendar_events_member']['fields']['onlineEventEvaluationData'] = array
(
    'exclude'                 => true,
    'search'                  => true,
    'inputType'               => 'checkbox',
    'eval'                    => array('tl_class'=>'w50'),
    'sql'                     => "mediumtext NULL"
);