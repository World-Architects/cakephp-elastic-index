<?php
namespace Psa\ElasticIndex\Shell;

use Cake\Chronos\Chronos;

trait PassedTimeTrait {

    /**
     * Start time of a process
     *
     * @var \DateTime|null
     */
    protected $_startTime = null;

    protected function _setStartTime()
    {
        $this->_startTime = Chronos::now();
    }

    protected function _showPassedTime()
    {
        $seconds = Chronos::now()->diffInSeconds($this->_startTime);
        $minutes = Chronos::now()->diffInMinutes($this->_startTime);
        $hours = Chronos::now()->diffInHours($this->_startTime);

        if ($hours > 0) {
            $seconds = $seconds - ($hours * 60 * 60);
        }

        if ($seconds > 60) {
            $seconds = $seconds - ($minutes * 60);
            $this->out($minutes . ' minutes and ' . $seconds . ' seconds');
        } else {
            $this->out($seconds . ' seconds');
        }
    }
}
