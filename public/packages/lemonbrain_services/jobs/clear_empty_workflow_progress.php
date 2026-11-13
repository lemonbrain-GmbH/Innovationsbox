<?php

declare(strict_types=1);

namespace Concrete\Package\LemonbrainServices\Job;

use Concrete\Core\Job\Job as AbstractJob;
use Concrete\Core\Workflow\EmptyWorkflow;
use Concrete\Core\Workflow\Progress\PageProgress;

class ClearEmptyWorkflowProgress extends AbstractJob
{
    public function getJobName()
    {
        return t('Clear Empty Workflow Progress');
    }

    public function getJobDescription()
    {
        return t('Deletes empty "Compare Versions" alert.');
    }

    public function run()
    {
        // retrieve all pending page workflow progresses
        $list = PageProgress::getPendingWorkflowProgressList();
        $r = $list->get();
        foreach ($r as $w) {
            $wp = $w->getWorkflowProgressObject();
            $wo = $wp->getWorkflowObject();
            if ($wo instanceof EmptyWorkflow) {
                $wp->delete();
            }
        }
    }
}
