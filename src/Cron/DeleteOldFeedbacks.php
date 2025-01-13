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
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

#[AsCronJob('weekly')]
class DeleteOldFeedbacks
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Connection $connection,
        private readonly string $deleteFeedbacksAfter,
    ) {
    }

    /**
     * @throws Exception
     */
    public function __invoke(): void
    {
        $this->framework->initialize();

        $datimToday = new \DateTimeImmutable(date('Y-m-d'));
        $datimExpired = $datimToday->modify('-'.$this->deleteFeedbacksAfter.' day');

        // Delete old feedbacks
        $this->connection->executeStatement('DELETE FROM tl_event_feedback WHERE dateAdded < ?', [$datimExpired->getTimestamp()]);

        // Delete old reminders
        $this->connection->executeStatement('DELETE FROM tl_event_feedback_reminder WHERE dateAdded < ?', [$datimExpired->getTimestamp()]);
    }
}
