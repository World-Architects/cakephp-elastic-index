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

    /**
     * Sets the start time
     *
     * @return void
     */
    protected function _setStartTime()
    {
        $this->_startTime = time();
    }

    /**
     * Calculates the times
     *
     * @param int $duration
     * @return array
     */
    protected function _calcTime($duration)
    {
        return [
            'days' => floor( $duration / (3600 * 24)),
            'hours' => floor( ($duration / 3600 ) % 24),
            'minutes' => floor( ( $duration / 60 ) % 60 ),
            'seconds' => ( $duration % 60 )
        ];
    }

    /**
     * Prints the passed time since _startStartTime()
     *
     * @return  void
     */
    protected function _showPassedTime()
    {
        $time = $this->_calcTime(time() - (strtotime('-3 days') - 37125));

        $output = [
            __n('{0} Day', '{0} Days', $time['days'], [$time['days']]),
            __n('{0} Hour', '{0} Hours', $time['hours'], [$time['hours']]),
            __n('{0} Minute', '{0} Minutes', $time['minutes'], [$time['minutes']]),
            __n('{0} Second', '{0} Seconds', $time['seconds'], [$time['seconds']])
        ];

        $this->out(implode(', ', $output));
    }
}
