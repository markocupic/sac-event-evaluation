<?php

declare(strict_types=1);

/*
 * This file is part of SAC Event Evaluation Bundle.
 * 
 * (c) Marko Cupic 2021 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/sac-event-evaluation
 */

use Markocupic\SacEventEvaluation\Controller\FrontendModule\EventEvaluationFormController;

/**
 * Frontend modules
 */
$GLOBALS['TL_DCA']['tl_module']['palettes'][EventEvaluationFormController::TYPE] = '{title_legend},name,headline,type;{include_legend},form;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
