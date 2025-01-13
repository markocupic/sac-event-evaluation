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

namespace Markocupic\SacEventFeedback\Cron;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCronJob;
use Contao\CoreBundle\Framework\ContaoFramework;
use Doctrine\DBAL\Exception;
use Markocupic\SacEventFeedback\FeedbackReminder\SendFeedbackReminder;

#[AsCronJob('minutely')]
class ExecuteEventReminderTasks
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly SendFeedbackReminder $sendFeedbackReminder,
    ) {
    }

    /**
     * @throws Exception
     */
    public function __invoke(): void
    {
        // Initialize the Contao framework
        $this->framework->initialize();

        $now = time();
        $this->sendFeedbackReminder->sendRemindersByExecutionDate($now, 20);
    }
}
