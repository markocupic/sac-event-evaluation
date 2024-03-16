<?php

declare(strict_types=1);

/*
 * This file is part of SAC Event Feedback.
 *
 * (c) Marko Cupic 2024 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/sac-event-feedback
 */

namespace Markocupic\SacEventFeedback;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class MarkocupicSacEventFeedback extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }


}
