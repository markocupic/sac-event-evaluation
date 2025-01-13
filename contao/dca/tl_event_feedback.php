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

use Contao\FormModel;
use Contao\DC_Table;
use Contao\DataContainer;

$GLOBALS['TL_DCA']['tl_event_feedback'] = [
    'config'   => [
        'dataContainer'    => DC_Table::class,
        'enableVersioning' => true,
        'sql'              => [
            'keys' => [
                'id'   => 'primary',
                'uuid' => 'index',
                'uuid' => 'unique',
            ],
        ],
    ],
    'list'     => [
        'sorting'           => [
            'mode'        => DataContainer::MODE_SORTABLE,
            'fields'      => ['dateAdded'],
            'flag'        => DataContainer::SORT_INITIAL_LETTER_ASC,
            'panelLayout' => 'filter;sort,search,limit',
        ],
        'label'             => [
            'fields' => [
                'uuid',
                'pid',
            ],
            'format' => '%s, %s',
        ],
        'global_operations' => [
            'all',
        ],
    ],
    'palettes' => [
        '__selector__' => ['addSubpalette'],
        'default'      => '{first_legend},pid,form,uuid,dateAdded;{survey_legend},learningEffectIndex,learningGoalsAchievedIndex,theoryAndPracticeBalanceIndex,recommendationIndex,safetyFeelingIndex,durationIndex,improvementOpportunity,highlights,comments,wildcard',
    ],
    'fields'   => [
        'id'                            => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'pid'                           => [
            'eval'       => ['rgxp' => 'natural', 'tl_class' => 'w50 wizard'],
            'foreignKey' => 'tl_calendar_events.title',
            'relation'   => ['type' => 'belongsTo', 'load' => 'lazy'],
            'sql'        => 'int(10) unsigned NOT NULL default 0',
        ],
        'tstamp'                        => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'dateAdded'                     => [
            'default'   => time(),
            'eval'      => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'exclude'   => true,
            'flag'      => 6,
            'inputType' => 'text',
            'sorting'   => true,
            'sql'       => 'int(11) unsigned NOT NULL default 0',
        ],
        'form'                          => [
            'eval'       => ['rgxp' => 'natural', 'tl_class' => 'w50 wizard'],
            'exclude'   => true,
            'foreignKey' => 'tl_form.title',
            'inputType'  => 'select',
            'options'    => FormModel::findAll()->fetchEach('id'),
            'relation'   => ['type' => 'belongsTo', 'load' => 'lazy'],
            'sql'        => 'int(10) unsigned NOT NULL default 0',
        ],
        'uuid'                          => [
            'eval'      => ['mandatory' => true, 'unique' => true, 'readonly' => true, 'tl_class' => 'w50'],
            'exclude'   => true,
            'filter'    => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => 'varchar(64) BINARY NULL',
        ],
        'learningEffectIndex'           => [
            'eval'      => ['mandatory' => true, 'readonly' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'],
            'exclude'   => true,
            'filter'    => true,
            'inputType' => 'select',
            'options'   => ['1', '2', '3', '4'],
            'reference' => $GLOBALS['TL_LANG']['tl_event_feedback']['learningEffectIndexReference'] ?? '',
            'search'    => true,
            'sorting'   => true,
            'sql'       => "char(1) NOT NULL default ''",
        ],
        'learningGoalsAchievedIndex'    => [
            'eval'      => ['mandatory' => true, 'readonly' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'],
            'exclude'   => true,
            'filter'    => true,
            'inputType' => 'select',
            'options'   => ['1', '2', '3', '4'],
            'reference' => $GLOBALS['TL_LANG']['tl_event_feedback']['learningEffectIndexReference'] ?? '',
            'search'    => true,
            'sorting'   => true,
            'sql'       => "char(1) NOT NULL default ''",
        ],
        'theoryAndPracticeBalanceIndex' => [
            'eval'      => ['mandatory' => true, 'readonly' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'],
            'exclude'   => true,
            'filter'    => true,
            'inputType' => 'select',
            'options'   => ['1', '2', '3', '4'],
            'reference' => $GLOBALS['TL_LANG']['tl_event_feedback']['learningEffectIndexReference'] ?? '',
            'search'    => true,
            'sorting'   => true,
            'sql'       => "char(1) NOT NULL default ''",
        ],
        'recommendationIndex'           => [
            'eval'      => ['mandatory' => true, 'readonly' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'],
            'exclude'   => true,
            'filter'    => true,
            'inputType' => 'select',
            'options'   => ['1', '2', '3', '4'],
            'reference' => $GLOBALS['TL_LANG']['tl_event_feedback']['learningEffectIndexReference'] ?? '',
            'search'    => true,
            'sorting'   => true,
            'sql'       => "char(1) NOT NULL default ''",
        ],
        'safetyFeelingIndex'            => [
            'eval'      => ['mandatory' => true, 'readonly' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'],
            'exclude'   => true,
            'filter'    => true,
            'inputType' => 'select',
            'options'   => ['1', '2', '3', '4'],
            'reference' => $GLOBALS['TL_LANG']['tl_event_feedback']['learningEffectIndexReference'] ?? '',
            'search'    => true,
            'sorting'   => true,
            'sql'       => "char(1) NOT NULL default ''",
        ],
        'durationIndex'                 => [
            'eval'      => ['mandatory' => true, 'readonly' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'],
            'exclude'   => true,
            'filter'    => true,
            'inputType' => 'select',
            'options'   => ['1', '2', '3', '4', '5'],
            'reference' => $GLOBALS['TL_LANG']['tl_event_feedback']['durationIndexReference'] ?? '',
            'search'    => true,
            'sorting'   => true,
            'sql'       => "char(1) NOT NULL default ''",
        ],
        'improvementOpportunity'        => [
            'eval'      => ['readonly' => true, 'rte' => 'tinyMCE', 'tl_class' => 'clr'],
            'exclude'   => true,
            'filter'    => true,
            'inputType' => 'textarea',
            'search'    => true,
            'sql'       => 'text NULL',
        ],
        'highlights'                    => [
            'eval'      => ['readonly' => true, 'rte' => 'tinyMCE', 'tl_class' => 'clr'],
            'exclude'   => true,
            'filter'    => true,
            'inputType' => 'textarea',
            'search'    => true,
            'sql'       => 'text NULL',
        ],
        'wildcard'                      => [
            'eval'      => ['rte' => 'tinyMCE', 'tl_class' => 'clr'],
            'exclude'   => true,
            'filter'    => true,
            'inputType' => 'textarea',
            'search'    => true,
            'sql'       => 'text NULL',
        ],
        'comments'                      => [
            'eval'      => ['readonly' => true, 'rte' => 'tinyMCE', 'tl_class' => 'clr'],
            'exclude'   => true,
            'filter'    => true,
            'inputType' => 'textarea',
            'search'    => true,
            'sql'       => 'text NULL',
        ],
    ],
];
