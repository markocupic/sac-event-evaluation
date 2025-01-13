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

use Contao\DC_Table;
use Contao\DataContainer;

$GLOBALS['TL_DCA']['tl_event_feedback_reminder'] = [
    'config'   => [
        'dataContainer' => DC_Table::class,
        'ptable'        => 'tl_calendar_events_member',
        'closed'        => false,
        'notDeletable'  => false,
        'notEditable'   => false,
        'notCopyable'   => false,
        'sql'           => [
            'keys' => [
                'id'                 => 'primary',
                'uuid'               => 'index',
                'uuid,executionDate' => 'unique',
            ],
        ],
    ],
    'list'     => [
        'sorting'           => [
            'mode'        => DataContainer::MODE_SORTABLE,
            'fields'      => ['uuid', 'dateAdded'],
            'flag'        => DataContainer::SORT_INITIAL_LETTER_ASC,
            'panelLayout' => 'filter;sort,search,limit',
        ],
        'label'             => [
            'fields' => ['uuid', 'pid'],
            'format' => '%s, %s',
        ],
        'global_operations' => [
            'all',
        ],
    ],
    'palettes' => [
        'default' => '{title_legend},uuid,dateAdded,executionDate,dispatched,dispatchTime',
    ],
    'fields'   => [
        'id'            => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'pid'           => [
            'foreignKey' => 'tl_calendar_events_member.CONCAT(firstname," ",lastname, " [", sacMemberId, "]")',
            'sql'        => 'int(10) unsigned NOT NULL default 0',
            'relation'   => [
                'type' => 'belongsTo',
                'load' => 'lazy',
            ],
        ],
        'tstamp'        => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'dateAdded'     => [
            'inputType' => 'text',
            'default'   => time(),
            'sorting'   => true,
            'flag'      => DataContainer::SORT_DAY_DESC,
            'eval'      => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql'       => 'int(10) unsigned NOT NULL default 0',
        ],
        'uuid'          => [
            'inputType' => 'text',
            'exclude'   => true,
            'search'    => true,
            'filter'    => true,
            'sorting'   => true,
            'eval'      => ['mandatory' => true, 'readonly' => true, 'tl_class' => 'w50'],
            'sql'       => "char(36) NOT NULL default ''",
        ],
        'dispatched'    => [
            'inputType' => 'checkbox',
            'filter'    => true,
            'sorting'   => true,
            'eval'      => ['isBoolean' => true, 'tl_class' => 'clr'],
            'sql'       => ['type' => 'boolean', 'default' => false],
        ],
        'dispatchTime'  => [
            'inputType' => 'text',
            'sorting'   => true,
            'flag'      => DataContainer::SORT_DAY_ASC,
            'eval'      => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql'       => "varchar(11) NOT NULL default ''",
        ],
        'executionDate' => [
            'inputType' => 'text',
            'sorting'   => true,
            'flag'      => DataContainer::SORT_DAY_ASC,
            'eval'      => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql'       => "varchar(11) NOT NULL default ''",
        ],
        'expiration'    => [
            'inputType' => 'text',
            'sorting'   => true,
            'flag'      => DataContainer::SORT_DAY_DESC,
            'eval'      => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql'       => "varchar(11) NOT NULL default ''",
        ],
    ],
];
